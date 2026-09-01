<?php
/**
 * Privacy-safe room availability builder.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\EmployeeDashboard;

use InvalidArgumentException;
use WorkshopRegistration\Domain\Scheduling\RoomReservation;
use WorkshopRegistration\Domain\Scheduling\SchedulingRules;

/**
 * Merges occupied ranges and calculates working-hour complements per room.
 */
final class AvailabilityTimelineBuilder {
	/**
	 * Build availability for every configured numbered room.
	 *
	 * @param SchedulingRules $rules        Current company rules.
	 * @param int             $capacity     Numbered room capacity.
	 * @param array           $reservations Active reservations for one date.
	 * @phpstan-param list<RoomReservation> $reservations
	 * @return list<RoomAvailability>
	 * @throws InvalidArgumentException When capacity is invalid.
	 */
	public function build( SchedulingRules $rules, int $capacity, array $reservations ): array {
		if ( $capacity < 1 ) {
			throw new InvalidArgumentException( 'Room capacity must be positive.' );
		}

		$rooms = array();

		for ( $slot = 1; $slot <= $capacity; ++$slot ) {
			$occupied = array();

			foreach ( $reservations as $reservation ) {
				if ( $reservation->slotNumber() !== $slot || ! $reservation->status()->reservesRoom() ) {
					continue;
				}

				$start = max( $rules->workdayStartMinute, $reservation->interval()->startMinute() );
				$end   = min( $rules->workdayEndMinute, $reservation->interval()->endMinute() );

				if ( $start < $end ) {
					$occupied[] = new AvailabilityPeriod( $start, $end );
				}
			}

			$merged    = $this->merge( $occupied );
			$available = $this->complement( $rules, $merged );
			$rooms[]   = new RoomAvailability( $slot, $merged, $available );
		}

		return $rooms;
	}

	/**
	 * Merge overlapping and adjacent occupied periods.
	 *
	 * @param array $periods Occupied periods.
	 * @phpstan-param list<AvailabilityPeriod> $periods
	 * @return list<AvailabilityPeriod>
	 */
	private function merge( array $periods ): array {
		usort( $periods, static fn( AvailabilityPeriod $left, AvailabilityPeriod $right ): int => $left->startMinute <=> $right->startMinute );
		$merged = array();

		foreach ( $periods as $period ) {
			$last_index = count( $merged ) - 1;

			if ( $last_index < 0 || $period->startMinute > $merged[ $last_index ]->endMinute ) {
				$merged[] = $period;
				continue;
			}

			$merged[ $last_index ] = new AvailabilityPeriod(
				$merged[ $last_index ]->startMinute,
				max( $merged[ $last_index ]->endMinute, $period->endMinute )
			);
		}

		return array_values( $merged );
	}

	/**
	 * Calculate free periods inside current working hours.
	 *
	 * @param SchedulingRules $rules    Current company rules.
	 * @param array           $occupied Merged occupied periods.
	 * @phpstan-param list<AvailabilityPeriod> $occupied
	 * @return list<AvailabilityPeriod>
	 */
	private function complement( SchedulingRules $rules, array $occupied ): array {
		$available = array();
		$cursor    = $rules->workdayStartMinute;

		foreach ( $occupied as $period ) {
			if ( $cursor < $period->startMinute ) {
				$available[] = new AvailabilityPeriod( $cursor, $period->startMinute );
			}

			$cursor = max( $cursor, $period->endMinute );
		}

		if ( $cursor < $rules->workdayEndMinute ) {
			$available[] = new AvailabilityPeriod( $cursor, $rules->workdayEndMinute );
		}

		return $available;
	}
}
