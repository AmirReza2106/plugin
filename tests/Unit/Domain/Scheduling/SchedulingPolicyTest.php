<?php
/**
 * Scheduling policy tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Domain\Scheduling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\Scheduling\InvalidBookingTime;
use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;
use WorkshopRegistration\Domain\Scheduling\SchedulingRules;

/**
 * Verifies all company booking-time constraints.
 */
final class SchedulingPolicyTest extends TestCase {
	/**
	 * Valid booking boundaries produce minute-based intervals.
	 *
	 * @param string $start_time     Submitted start time.
	 * @param string $end_time       Submitted end time.
	 * @param int    $expected_start Expected start minute.
	 * @param int    $expected_end   Expected end minute.
	 */
	#[DataProvider( 'validTimes' )]
	public function test_it_accepts_valid_booking_times(
		string $start_time,
		string $end_time,
		int $expected_start,
		int $expected_end
	): void {
		$interval = ( new SchedulingPolicy() )->createInterval( $start_time, $end_time );

		self::assertSame( $expected_start, $interval->startMinute() );
		self::assertSame( $expected_end, $interval->endMinute() );
	}

	/**
	 * Invalid booking times expose a presentation-independent reason.
	 *
	 * @param string $start_time      Submitted start time.
	 * @param string $end_time        Submitted end time.
	 * @param string $expected_reason Expected validation reason.
	 */
	#[DataProvider( 'invalidTimes' )]
	public function test_it_rejects_invalid_booking_times(
		string $start_time,
		string $end_time,
		string $expected_reason
	): void {
		try {
			( new SchedulingPolicy() )->createInterval( $start_time, $end_time );
			self::fail( 'Expected an invalid booking time exception.' );
		} catch ( InvalidBookingTime $exception ) {
			self::assertSame( $expected_reason, $exception->reason() );
		}
	}

	/**
	 * Administrator rules replace the initial working-hour and duration defaults.
	 */
	public function test_it_uses_configured_scheduling_rules(): void {
		$policy   = new SchedulingPolicy( new SchedulingRules( 480, 1020, 15, 120 ) );
		$interval = $policy->createInterval( '08:00', '09:30' );

		self::assertSame( 480, $interval->startMinute() );
		self::assertSame( 570, $interval->endMinute() );
	}

	/**
	 * Provide valid booking times and their minute values.
	 *
	 * @return iterable<string, array{string, string, int, int}>
	 */
	public static function validTimes(): iterable {
		yield 'minimum at opening' => array( '09:00', '09:30', 540, 570 );
		yield 'maximum at closing' => array( '17:00', '18:00', 1020, 1080 );
		yield 'forty-five minutes' => array( '17:15', '18:00', 1035, 1080 );
	}

	/**
	 * Provide invalid booking times and expected reasons.
	 *
	 * @return iterable<string, array{string, string, string}>
	 */
	public static function invalidTimes(): iterable {
		yield 'non-strict format' => array( '9:00', '09:30', InvalidBookingTime::INVALID_FORMAT );
		yield 'arbitrary minute' => array( '09:05', '09:35', InvalidBookingTime::INVALID_INCREMENT );
		yield 'before opening' => array( '08:45', '09:15', InvalidBookingTime::OUTSIDE_WORKING_HOURS );
		yield 'after closing' => array( '17:30', '18:15', InvalidBookingTime::OUTSIDE_WORKING_HOURS );
		yield 'too short' => array( '09:00', '09:15', InvalidBookingTime::INVALID_DURATION );
		yield 'too long' => array( '09:00', '10:15', InvalidBookingTime::INVALID_DURATION );
		yield 'reversed' => array( '10:00', '09:30', InvalidBookingTime::INVALID_DURATION );
	}
}
