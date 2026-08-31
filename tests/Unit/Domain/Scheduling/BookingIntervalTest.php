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
 * Verifies cleanup-aware interval conflict semantics.
 */
final class BookingIntervalTest extends TestCase {
	/**
	 * A full cleanup gap is required in either chronological direction.
	 */
	public function test_it_applies_the_cleanup_gap_symmetrically(): void {
		$policy   = new SchedulingPolicy();
		$existing = $policy->createInterval( '09:00', '10:00' );

		self::assertTrue( $existing->conflictsWith( $policy->createInterval( '10:00', '10:30' ), 15 ) );
		self::assertTrue( $existing->conflictsWith( BookingInterval::fromMinutes( 614, 644 ), 15 ) );
		self::assertFalse( $existing->conflictsWith( $policy->createInterval( '10:15', '10:45' ), 15 ) );
		self::assertFalse( $policy->createInterval( '10:15', '10:45' )->conflictsWith( $existing, 15 ) );
	}
}
