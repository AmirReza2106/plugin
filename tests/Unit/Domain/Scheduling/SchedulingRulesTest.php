<?php
/**
 * Scheduling rules tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Domain\Scheduling;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\Scheduling\SchedulingRules;

/**
 * Verifies cross-field administrator scheduling constraints.
 */
final class SchedulingRulesTest extends TestCase {
	/**
	 * Invalid combinations are rejected at the domain boundary.
	 *
	 * @param int $start Working-day start minute.
	 * @param int $end   Working-day end minute.
	 * @param int $min   Minimum duration.
	 * @param int $max   Maximum duration.
	 */
	#[DataProvider( 'invalidRules' )]
	public function test_it_rejects_invalid_rule_combinations( int $start, int $end, int $min, int $max ): void {
		$this->expectException( InvalidArgumentException::class );

		new SchedulingRules( $start, $end, $min, $max );
	}

	/**
	 * Provide invalid scheduling combinations.
	 *
	 * @return iterable<string, array{int, int, int, int}>
	 */
	public static function invalidRules(): iterable {
		yield 'reversed working day' => array( 1080, 540, 30, 60 );
		yield 'unaligned start' => array( 541, 1080, 30, 60 );
		yield 'minimum below increment' => array( 540, 1080, 0, 60 );
		yield 'maximum below minimum' => array( 540, 1080, 60, 30 );
		yield 'unaligned duration' => array( 540, 1080, 30, 50 );
		yield 'duration exceeds day' => array( 540, 600, 30, 75 );
	}
}
