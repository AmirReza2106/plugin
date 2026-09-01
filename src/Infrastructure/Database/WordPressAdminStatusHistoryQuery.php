<?php
/**
 * WordPress administrator status history query.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

use WorkshopRegistration\Application\AdminRequests\AdminStatusHistoryItem;
use WorkshopRegistration\Application\Contracts\AdminStatusHistoryQuery;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Domain\WorkshopStatus;
use wpdb;

/**
 * Reads status history in one bounded query for the current request page.
 */
final class WordPressAdminStatusHistoryQuery implements AdminStatusHistoryQuery {
	/**
	 * Create the history query.
	 *
	 * @param wpdb   $database WordPress database connection.
	 * @param Tables $tables   Plugin table names.
	 */
	public function __construct( private wpdb $database, private Tables $tables ) {
	}

	/**
	 * Find status events grouped by request ID.
	 *
	 * @param array $request_ids Request database IDs.
	 * @return array<int, list<AdminStatusHistoryItem>>
	 * @phpstan-param list<int> $request_ids
	 * @throws PersistenceFailure When identifiers, database operations, or stored rows are invalid.
	 */
	public function findByRequestIds( array $request_ids ): array {
		if ( array() === $request_ids ) {
			return array();
		}

		$request_ids = array_values( array_unique( array_map( 'intval', $request_ids ) ) );

		if ( count( $request_ids ) > 100 || in_array( 0, $request_ids, true ) ) {
			throw new PersistenceFailure( 'Administrator history query parameters are invalid.' );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $request_ids ), '%d' ) );
		$query        = $this->database->prepare(
			"SELECT history.request_id, history.from_status, history.to_status,
			history.previous_slot_number, history.new_slot_number, history.actor_user_id,
			history.created_at, users.display_name AS actor_display_name
			FROM %i AS history
			LEFT JOIN %i AS users ON users.ID = history.actor_user_id
			WHERE history.request_id IN ({$placeholders})
			ORDER BY history.request_id ASC, history.created_at ASC, history.id ASC",
			$this->tables->statusHistory(),
			$this->database->users,
			...$request_ids
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->database->get_results( $query, ARRAY_A );

		if ( null === $rows || '' !== $this->database->last_error ) {
			throw new PersistenceFailure( 'Administrator request history could not be read.' );
		}

		$history = array_fill_keys( $request_ids, array() );

		foreach ( $rows as $row ) {
			$request_id = (int) $row['request_id'];
			$from       = null === $row['from_status'] ? null : WorkshopStatus::tryFrom( (string) $row['from_status'] );
			$to         = WorkshopStatus::tryFrom( (string) $row['to_status'] );

			if ( ! array_key_exists( $request_id, $history ) || null === $to || ( null !== $row['from_status'] && null === $from ) ) {
				throw new PersistenceFailure( 'A stored administrator history row is invalid.' );
			}

			$history[ $request_id ][] = new AdminStatusHistoryItem(
				$request_id,
				$from,
				$to,
				null === $row['previous_slot_number'] ? null : (int) $row['previous_slot_number'],
				null === $row['new_slot_number'] ? null : (int) $row['new_slot_number'],
				null === $row['actor_user_id'] ? null : (int) $row['actor_user_id'],
				null === $row['actor_display_name'] ? null : (string) $row['actor_display_name'],
				(string) $row['created_at']
			);
		}

		return $history;
	}
}
