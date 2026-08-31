<?php
/**
 * Workshop repository contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

use WorkshopRegistration\Application\Registration\NewWorkshopRecord;
use WorkshopRegistration\Domain\Scheduling\RoomReservation;

/**
 * Persists workshop requests and provides scheduling projections.
 */
interface WorkshopRepository {
	/**
	 * Find active reservations for one local workshop date.
	 *
	 * @param string $workshop_date Workshop date in Y-m-d format.
	 * @return array
	 * @phpstan-return list<RoomReservation>
	 */
	public function findActiveReservationsByDate( string $workshop_date ): array;

	/**
	 * Insert a pending workshop request.
	 *
	 * @param NewWorkshopRecord $record New workshop persistence record.
	 */
	public function insert( NewWorkshopRecord $record ): int;

	/**
	 * Record the initial pending status event.
	 *
	 * @param int    $request_id Request database ID.
	 * @param int    $slot_number Assigned room slot.
	 * @param string $created_at UTC database timestamp.
	 */
	public function insertInitialHistory( int $request_id, int $slot_number, string $created_at ): void;
}
