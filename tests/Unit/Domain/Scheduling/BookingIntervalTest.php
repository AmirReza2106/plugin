<?php
/**
 * Booking interval tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Domain\Scheduling;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\Scheduling\BookingInterval;
use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;

/**
 * Verifies half-open interval conflict semantics without a meeting gap.
 */
final class BookingIntervalTest extends TestCase {
	/**
	 * A meeting can begin at the exact minute another meeting ends.
	 */
	public function test_exact_end_and_start_times_do_not_conflict(): void {
		$policy   = new SchedulingPolicy();
		$existing = $policy->createInterval( '09:00', '10:00' );

		self::assertFalse( $existing->conflictsWith( $policy->createInterval( '10:00', '10:30' ) ) );
		self::assertTrue( $existing->conflictsWith( BookingInterval::fromMinutes( 599, 629 ) ) );
		self::assertFalse( $policy->createInterval( '10:00', '10:30' )->conflictsWith( $existing ) );
	}
}
