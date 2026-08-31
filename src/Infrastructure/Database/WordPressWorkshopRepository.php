<?php
/**
 * WordPress workshop repository.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

use Throwable;
use WorkshopRegistration\Application\Contracts\WorkshopRepository;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Application\Registration\NewWorkshopRecord;
use WorkshopRegistration\Domain\Scheduling\RoomReservation;
use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;
use WorkshopRegistration\Domain\WorkshopStatus;
use wpdb;

/**
 * Maps custom WordPress table rows to application and domain objects.
 */
final class WordPressWorkshopRepository implements WorkshopRepository {
	/**
	 * Create the workshop repository.
	 *
	 * @param wpdb             $database          WordPress database connection.
	 * @param Tables           $tables            Plugin table names.
	 * @param SchedulingPolicy $scheduling_policy Booking-time policy.
	 */
	public function __construct(
		private wpdb $database,
		private Tables $tables,
		private SchedulingPolicy $scheduling_policy
	) {
	}

	/**
	 * Find active reservations for one local workshop date.
	 *
	 * @param string $workshop_date Workshop date in Y-m-d format.
	 * @return array
	 * @phpstan-return list<RoomReservation>
	 * @throws PersistenceFailure When rows cannot be read or mapped safely.
	 */
	public function findActiveReservationsByDate( string $workshop_date ): array {
		$table = $this->tables->requests();
		$query = $this->database->prepare(
			'SELECT start_time, end_time, status, slot_number
			FROM %i
			WHERE workshop_date = %s AND status IN (%s, %s)
			ORDER BY start_time ASC, id ASC',
			$table,
			$workshop_date,
			WorkshopStatus::Pending->value,
			WorkshopStatus::Approved->value
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->database->get_results( $query, ARRAY_A );

		if ( null === $rows || '' !== $this->database->last_error ) {
			throw new PersistenceFailure( 'Workshop reservations could not be read.' );
		}

		$reservations = array();

		try {
			foreach ( $rows as $row ) {
				$status = WorkshopStatus::tryFrom( (string) $row['status'] );

				if ( null === $status || null === $row['slot_number'] ) {
					throw new PersistenceFailure( 'An active reservation row is invalid.' );
				}

				$reservations[] = new RoomReservation(
					$this->scheduling_policy->createInterval(
						substr( (string) $row['start_time'], 0, 5 ),
						substr( (string) $row['end_time'], 0, 5 )
					),
					$status,
					(int) $row['slot_number']
				);
			}
		} catch ( Throwable $exception ) {
			if ( $exception instanceof PersistenceFailure ) {
				throw $exception;
			}

			throw new PersistenceFailure( 'A workshop reservation row is invalid.', 0, $exception );
		}

		return $reservations;
	}

	/**
	 * Insert a pending workshop request.
	 *
	 * @param NewWorkshopRecord $record New workshop persistence record.
	 * @throws PersistenceFailure When the insert fails.
	 */
	public function insert( NewWorkshopRecord $record ): int {
		$data = $record->registration;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $this->database->insert(
			$this->tables->requests(),
			array(
				'public_reference'    => $record->publicReference,
				'tracking_token_hash' => $record->trackingTokenHash,
				'first_name'          => $data->firstName,
				'last_name'           => $data->lastName,
				'mobile'              => $data->mobile,
				'mobile_normalized'   => $data->mobileNormalized,
				'email'               => $data->email,
				'workshop_title'      => $data->workshopTitle,
				'workshop_date'       => $data->workshopDate,
				'start_time'          => $data->startTime,
				'end_time'            => $data->endTime,
				'site_timezone'       => $data->siteTimezone,
				'description'         => $data->description,
				'status'              => WorkshopStatus::Pending->value,
				'slot_number'         => $record->slotNumber,
				'created_at'          => $record->createdAt,
				'updated_at'          => $record->createdAt,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted || $this->database->insert_id < 1 ) {
			throw new PersistenceFailure( 'The workshop request could not be stored.' );
		}

		return $this->database->insert_id;
	}

	/**
	 * Record the initial pending status event.
	 *
	 * @param int    $request_id Request database ID.
	 * @param int    $slot_number Assigned room slot.
	 * @param string $created_at UTC database timestamp.
	 * @throws PersistenceFailure When the history insert fails.
	 */
	public function insertInitialHistory( int $request_id, int $slot_number, string $created_at ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $this->database->insert(
			$this->tables->statusHistory(),
			array(
				'request_id'           => $request_id,
				'from_status'          => null,
				'to_status'            => WorkshopStatus::Pending->value,
				'previous_slot_number' => null,
				'new_slot_number'      => $slot_number,
				'actor_user_id'        => null,
				'created_at'           => $created_at,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		if ( false === $inserted ) {
			throw new PersistenceFailure( 'The workshop status history could not be stored.' );
		}
	}
}
