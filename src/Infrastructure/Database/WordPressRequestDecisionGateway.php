<?php
/**
 * Transactional WordPress request decision gateway.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

use Throwable;
use WorkshopRegistration\Application\Contracts\RequestDecisionGateway;
use WorkshopRegistration\Application\Exception\InvalidStatusTransition;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Application\Exception\RequestNotFound;
use WorkshopRegistration\Domain\WorkshopStatus;
use wpdb;

/**
 * Locks one request row while updating its status and audit history.
 */
final class WordPressRequestDecisionGateway implements RequestDecisionGateway {
	/**
	 * Create the decision gateway.
	 *
	 * @param wpdb   $database WordPress database connection.
	 * @param Tables $tables   Plugin table names.
	 */
	public function __construct( private wpdb $database, private Tables $tables ) {
	}

	/**
	 * Apply one final administrator decision atomically.
	 *
	 * @param int            $request_id   Request database ID.
	 * @param WorkshopStatus $target        Approved or rejected target status.
	 * @param int            $actor_user_id Administrator user ID.
	 * @param string         $changed_at    UTC database timestamp.
	 * @throws RequestNotFound         When the request does not exist.
	 * @throws InvalidStatusTransition When the request is no longer pending.
	 * @throws PersistenceFailure      When a database operation fails.
	 */
	public function decide( int $request_id, WorkshopStatus $target, int $actor_user_id, string $changed_at ): void {
		$this->executeStatement( 'START TRANSACTION' );

		try {
			$row = $this->findForUpdate( $request_id );

			if ( null === $row ) {
				throw new RequestNotFound( 'The workshop request does not exist.' );
			}

			if ( WorkshopStatus::Pending->value !== (string) $row['status'] ) {
				throw new InvalidStatusTransition( 'Only pending requests can receive a decision.' );
			}

			$previous_slot = null === $row['slot_number'] ? null : (int) $row['slot_number'];
			$new_slot      = WorkshopStatus::Rejected === $target ? null : $previous_slot;

			if ( null === $previous_slot ) {
				throw new PersistenceFailure( 'A pending request has no assigned room.' );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$updated = $this->database->update(
				$this->tables->requests(),
				array(
					'status'            => $target->value,
					'slot_number'       => $new_slot,
					'reviewed_by'       => $actor_user_id,
					'status_changed_at' => $changed_at,
					'updated_at'        => $changed_at,
				),
				array(
					'id'     => $request_id,
					'status' => WorkshopStatus::Pending->value,
				),
				array( '%s', '%d', '%d', '%s', '%s' ),
				array( '%d', '%s' )
			);

			if ( 1 !== $updated ) {
				throw new PersistenceFailure( 'The workshop request decision could not be stored.' );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->database->insert(
				$this->tables->statusHistory(),
				array(
					'request_id'           => $request_id,
					'from_status'          => WorkshopStatus::Pending->value,
					'to_status'            => $target->value,
					'previous_slot_number' => $previous_slot,
					'new_slot_number'      => $new_slot,
					'actor_user_id'        => $actor_user_id,
					'created_at'           => $changed_at,
				),
				array( '%d', '%s', '%s', '%d', '%d', '%d', '%s' )
			);

			if ( false === $inserted ) {
				throw new PersistenceFailure( 'The workshop decision history could not be stored.' );
			}

			$this->executeStatement( 'COMMIT' );
		} catch ( Throwable $exception ) {
			// Preserve the decision failure even if rollback also fails.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->database->query( 'ROLLBACK' );
			throw $exception;
		}
	}

	/**
	 * Lock and load current status allocation fields.
	 *
	 * @param int $request_id Request database ID.
	 * @return array{status:string,slot_number:string|null}|null
	 * @throws PersistenceFailure When the row cannot be read.
	 */
	private function findForUpdate( int $request_id ): ?array {
		$query = $this->database->prepare(
			'SELECT status, slot_number FROM %i WHERE id = %d FOR UPDATE',
			$this->tables->requests(),
			$request_id
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->database->get_row( $query, ARRAY_A );

		if ( null === $row ) {
			if ( '' !== $this->database->last_error ) {
				throw new PersistenceFailure( 'The workshop request could not be locked.' );
			}

			return null;
		}

		return array(
			'status'      => (string) $row['status'],
			'slot_number' => null === $row['slot_number'] ? null : (string) $row['slot_number'],
		);
	}

	/**
	 * Execute a fixed transaction statement.
	 *
	 * @param string $statement Fixed transaction statement.
	 * @throws PersistenceFailure When the statement fails.
	 */
	private function executeStatement( string $statement ): void {
		// Callers provide only fixed statements defined in this class.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $this->database->query( $statement ) ) {
			throw new PersistenceFailure( 'The request decision transaction failed.' );
		}
	}
}
