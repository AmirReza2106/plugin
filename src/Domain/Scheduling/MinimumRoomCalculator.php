<?php
/**
 * Minimum room capacity calculator.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

/**
 * Calculates peak concurrent room occupancy.
 */
final class MinimumRoomCalculator {
	/**
	 * Calculate the theoretical minimum room count.
	 *
	 * End events are processed before starts at the same minute, preserving
	 * half-open interval semantics.
	 *
	 * @param array $reservations Reservations for one date.
	 * @phpstan-param list<RoomReservation> $reservations
	 */
	public function calculate( array $reservations ): int {
		$events = array();

		foreach ( $reservations as $reservation ) {
			if ( ! $reservation->status()->reservesRoom() ) {
				continue;
			}

			$events[] = array(
				'minute' => $reservation->interval()->startMinute(),
				'delta'  => 1,
			);
			$events[] = array(
				'minute' => $reservation->interval()->endMinute(),
				'delta'  => -1,
			);
		}

		usort(
			$events,
			static fn( array $left, array $right ): int => array( $left['minute'], $left['delta'] ) <=> array( $right['minute'], $right['delta'] )
		);

		$current = 0;
		$maximum = 0;

		foreach ( $events as $event ) {
			$current += $event['delta'];
			$maximum  = max( $maximum, $current );
		}

		return $maximum;
	}
}
