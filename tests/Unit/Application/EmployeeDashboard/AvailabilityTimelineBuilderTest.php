<?php
/**
 * Availability timeline builder tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Application\EmployeeDashboard;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Application\EmployeeDashboard\AvailabilityTimelineBuilder;
use WorkshopRegistration\Domain\Scheduling\BookingInterval;
use WorkshopRegistration\Domain\Scheduling\RoomReservation;
use WorkshopRegistration\Domain\Scheduling\SchedulingRules;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Verifies privacy-safe occupied and available room projections.
 */
final class AvailabilityTimelineBuilderTest extends TestCase {
	/**
	 * An empty room is available for the complete working day.
	 */
	public function test_empty_rooms_are_available_for_the_working_day(): void {
		$rooms = ( new AvailabilityTimelineBuilder() )->build( SchedulingRules::defaults(), 2, array() );

		self::assertCount( 2, $rooms );
		self::assertSame( array(), $rooms[0]->occupied );
		self::assertSame( 540, $rooms[0]->available[0]->startMinute );
		self::assertSame( 1080, $rooms[0]->available[0]->endMinute );
	}

	/**
	 * Adjacent and overlapping reservations merge for clear visualization.
	 */
	public function test_it_merges_occupied_periods_and_calculates_complements(): void {
		$reservations = array(
			new RoomReservation( BookingInterval::fromMinutes( 540, 600 ), WorkshopStatus::Pending, 1 ),
			new RoomReservation( BookingInterval::fromMinutes( 600, 660 ), WorkshopStatus::Approved, 1 ),
			new RoomReservation( BookingInterval::fromMinutes( 645, 690 ), WorkshopStatus::Approved, 1 ),
			new RoomReservation( BookingInterval::fromMinutes( 720, 750 ), WorkshopStatus::Rejected, null ),
		);

		$room = ( new AvailabilityTimelineBuilder() )->build( SchedulingRules::defaults(), 1, $reservations )[0];

		self::assertCount( 1, $room->occupied );
		self::assertSame( 540, $room->occupied[0]->startMinute );
		self::assertSame( 690, $room->occupied[0]->endMinute );
		self::assertCount( 1, $room->available );
		self::assertSame( 690, $room->available[0]->startMinute );
		self::assertSame( 1080, $room->available[0]->endMinute );
	}

	/**
	 * Existing bookings are clipped to newly configured working hours.
	 */
	public function test_it_clips_legacy_reservations_to_current_working_hours(): void {
		$rules = new SchedulingRules( 600, 1020, 30, 60 );
		$room  = ( new AvailabilityTimelineBuilder() )->build(
			$rules,
			1,
			array( new RoomReservation( BookingInterval::fromMinutes( 540, 630 ), WorkshopStatus::Approved, 1 ) )
		)[0];

		self::assertSame( 600, $room->occupied[0]->startMinute );
		self::assertSame( 630, $room->occupied[0]->endMinute );
	}
}
