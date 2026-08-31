<?php
/**
 * Minimum room capacity calculator.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

use InvalidArgumentException;

/**
 * Calculates peak concurrent occupancy with cleanup periods included.
 */
final class MinimumRoomCalculator {
	/**
	 * Required cleanup gap in minutes.
	 *
	 * @var int
	 */
	private int $gap_minutes;

	/**
	 * Create a minimum-room calculator.
	 *
	 * @param int $gap_minutes Required cleanup gap in minutes.
	 * @throws InvalidArgumentException When the gap is negative.
	 */
	public function __construct( int $gap_minutes = SchedulingPolicy::CLEANUP_GAP_MINUTES ) {
		if ( $gap_minutes < 0 ) {
			throw new InvalidArgumentException( 'The cleanup gap cannot be negative.' );
		}

		$this->gap_minutes = $gap_minutes;
	}

	/**
	 * Calculate the theoretical minimum room count.
	 *
	 * End events are processed before starts at the same minute, preserving
	 * half-open interval semantics after the cleanup gap is applied.
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
				'minute' => $reservation->interval()->endMinute() + $this->gap_minutes,
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
