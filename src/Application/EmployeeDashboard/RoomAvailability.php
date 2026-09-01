<?php
/**
 * Room availability projection.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\EmployeeDashboard;

/**
 * Contains privacy-safe occupied and available periods for one numbered room.
 */
final class RoomAvailability {
	/**
	 * Create a room availability projection.
	 *
	 * @param int   $slotNumber Room slot number.
	 * @param array $occupied   Merged occupied periods.
	 * @param array $available  Available periods.
	 * @phpstan-param list<AvailabilityPeriod> $occupied
	 * @phpstan-param list<AvailabilityPeriod> $available
	 */
	public function __construct(
		public readonly int $slotNumber,
		public readonly array $occupied,
		public readonly array $available
	) {
	}
}
