<?php
/**
 * Stable slot allocator tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Domain\Scheduling;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\Scheduling\RoomReservation;
use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;
use WorkshopRegistration\Domain\Scheduling\StableSlotAllocator;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Verifies stable lowest-room allocation and capacity handling.
 */
final class StableSlotAllocatorTest extends TestCase {
	/**
	 * The lowest room without an active conflict is selected.
	 */
	public function test_it_selects_the_lowest_available_slot(): void {
		$policy       = new SchedulingPolicy();
		$allocator    = new StableSlotAllocator();
		$requested    = $policy->createInterval( '09:30', '10:00' );
		$reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Pending, 1 ),
			new RoomReservation( $policy->createInterval( '09:00', '09:30' ), WorkshopStatus::Approved, 2 ),
		);

		self::assertSame( 2, $allocator->allocate( $requested, $reservations, 3 ) );
	}

	/**
	 * Rejected requests release their former room capacity.
	 */
	public function test_it_ignores_rejected_requests(): void {
		$policy       = new SchedulingPolicy();
		$reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Rejected, null ),
		);

		self::assertSame(
			1,
			( new StableSlotAllocator() )->allocate( $policy->createInterval( '09:30', '10:00' ), $reservations, 1 )
		);
	}

	/**
	 * The first room becomes reusable exactly when its booking ends.
	 */
	public function test_it_reuses_a_slot_at_the_previous_end_time(): void {
		$policy       = new SchedulingPolicy();
		$reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Approved, 1 ),
		);

		self::assertSame(
			1,
			( new StableSlotAllocator() )->allocate( $policy->createInterval( '10:00', '10:30' ), $reservations, 1 )
		);
	}

	/**
	 * No assignment is returned when every configured room conflicts.
	 */
	public function test_it_reports_capacity_exhaustion(): void {
		$policy       = new SchedulingPolicy();
		$reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Pending, 1 ),
			new RoomReservation( $policy->createInterval( '09:15', '10:15' ), WorkshopStatus::Approved, 2 ),
		);

		self::assertNull(
			( new StableSlotAllocator() )->allocate( $policy->createInterval( '09:30', '10:00' ), $reservations, 2 )
		);
	}
}
