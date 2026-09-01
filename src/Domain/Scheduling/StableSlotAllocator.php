<?php
/**
 * Stable room slot allocator.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

use InvalidArgumentException;

/**
 * Assigns the lowest free room without changing existing assignments.
 */
final class StableSlotAllocator {
	/**
	 * Find the lowest available stable room slot.
	 *
	 * @param BookingInterval $requested    Requested interval.
	 * @param array           $reservations Existing reservations for the same date.
	 * @param int             $capacity     Configured room count.
	 * @phpstan-param list<RoomReservation> $reservations
	 * @throws InvalidArgumentException When capacity is not positive.
	 */
	public function allocate( BookingInterval $requested, array $reservations, int $capacity ): ?int {
		if ( $capacity < 1 ) {
			throw new InvalidArgumentException( 'Room capacity must be positive.' );
		}

		for ( $slot = 1; $slot <= $capacity; ++$slot ) {
			if ( $this->isSlotAvailable( $requested, $reservations, $slot ) ) {
				return $slot;
			}
		}

		return null;
	}

	/**
	 * Determine whether one room is free for the requested interval.
	 *
	 * @param BookingInterval $requested    Requested interval.
	 * @param array           $reservations Existing reservations for the same date.
	 * @param int             $slot          Room slot to inspect.
	 * @phpstan-param list<RoomReservation> $reservations
	 */
	private function isSlotAvailable( BookingInterval $requested, array $reservations, int $slot ): bool {
		foreach ( $reservations as $reservation ) {
			if ( ! $reservation->status()->reservesRoom() || $slot !== $reservation->slotNumber() ) {
				continue;
			}

			if ( $requested->conflictsWith( $reservation->interval() ) ) {
				return false;
			}
		}

		return true;
	}
}
