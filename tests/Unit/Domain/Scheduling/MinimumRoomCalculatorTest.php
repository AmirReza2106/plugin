<?php
/**
 * Minimum room calculator tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Domain\Scheduling;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\Scheduling\MinimumRoomCalculator;
use WorkshopRegistration\Domain\Scheduling\RoomReservation;
use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Verifies peak occupancy calculations including cleanup time.
 */
final class MinimumRoomCalculatorTest extends TestCase {
	/**
	 * Overlapping valid bookings need three rooms after applying the gap rule.
	 */
	public function test_it_calculates_the_cleanup_aware_example(): void {
		$policy       = new SchedulingPolicy();
		$reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Approved, 1 ),
			new RoomReservation( $policy->createInterval( '09:30', '10:30' ), WorkshopStatus::Pending, 2 ),
			new RoomReservation( $policy->createInterval( '10:00', '11:00' ), WorkshopStatus::Approved, 3 ),
			new RoomReservation( $policy->createInterval( '11:15', '12:00' ), WorkshopStatus::Pending, 1 ),
		);

		self::assertSame( 3, ( new MinimumRoomCalculator() )->calculate( $reservations ) );
	}

	/**
	 * A booking beginning after the full gap can reuse the same room.
	 */
	public function test_end_events_are_processed_before_equal_start_events(): void {
		$policy       = new SchedulingPolicy();
		$reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Approved, 1 ),
			new RoomReservation( $policy->createInterval( '10:15', '10:45' ), WorkshopStatus::Pending, 1 ),
		);

		self::assertSame( 1, ( new MinimumRoomCalculator() )->calculate( $reservations ) );
	}

	/**
	 * Rejected requests do not contribute to required capacity.
	 */
	public function test_it_excludes_rejected_requests(): void {
		$policy       = new SchedulingPolicy();
		$reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Approved, 1 ),
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Rejected, null ),
		);

		self::assertSame( 1, ( new MinimumRoomCalculator() )->calculate( $reservations ) );
	}

	/**
	 * No active requests require no rooms.
	 */
	public function test_it_returns_zero_for_no_reservations(): void {
		self::assertSame( 0, ( new MinimumRoomCalculator() )->calculate( array() ) );
	}
}
