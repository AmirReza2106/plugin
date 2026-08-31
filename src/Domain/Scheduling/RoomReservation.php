<?php
/**
 * Assigned room reservation.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

use InvalidArgumentException;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Combines a booking interval, status, and stable room assignment.
 */
final class RoomReservation {
	/**
	 * Create a room reservation.
	 *
	 * @param BookingInterval $interval    Booking interval.
	 * @param WorkshopStatus  $status      Request status.
	 * @param int|null        $slot_number Assigned room slot.
	 * @throws InvalidArgumentException When an active reservation has no valid slot.
	 */
	public function __construct(
		private BookingInterval $interval,
		private WorkshopStatus $status,
		private ?int $slot_number
	) {
		if ( null !== $slot_number && $slot_number < 1 ) {
			throw new InvalidArgumentException( 'A room slot number must be positive.' );
		}

		if ( $status->reservesRoom() && null === $slot_number ) {
			throw new InvalidArgumentException( 'An active reservation must have a room slot.' );
		}
	}

	/**
	 * Get the booking interval.
	 */
	public function interval(): BookingInterval {
		return $this->interval;
	}

	/**
	 * Get the request status.
	 */
	public function status(): WorkshopStatus {
		return $this->status;
	}

	/**
	 * Get the assigned room slot.
	 */
	public function slotNumber(): ?int {
		return $this->slot_number;
	}
}
