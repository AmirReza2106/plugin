<?php
/**
 * Configurable company scheduling rules.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

use InvalidArgumentException;

/**
 * Holds validated working hours and duration constraints.
 */
final class SchedulingRules {
	public const TIME_INCREMENT_MINUTES = 15;

	/**
	 * Create validated scheduling rules.
	 *
	 * @param int $workdayStartMinute Start minute after midnight.
	 * @param int $workdayEndMinute   End minute after midnight.
	 * @param int $minimumDuration    Minimum booking duration in minutes.
	 * @param int $maximumDuration    Maximum booking duration in minutes.
	 * @throws InvalidArgumentException When the rules are inconsistent.
	 */
	public function __construct(
		public readonly int $workdayStartMinute,
		public readonly int $workdayEndMinute,
		public readonly int $minimumDuration,
		public readonly int $maximumDuration
	) {
		$increment = self::TIME_INCREMENT_MINUTES;

		if (
			$workdayStartMinute < 0
			|| $workdayEndMinute > 1440
			|| $workdayStartMinute >= $workdayEndMinute
			|| 0 !== $workdayStartMinute % $increment
			|| 0 !== $workdayEndMinute % $increment
		) {
			throw new InvalidArgumentException( 'Working hours must be ordered 15-minute values within one day.' );
		}

		$workday_length = $workdayEndMinute - $workdayStartMinute;

		if (
			$minimumDuration < $increment
			|| $maximumDuration < $minimumDuration
			|| $maximumDuration > $workday_length
			|| 0 !== $minimumDuration % $increment
			|| 0 !== $maximumDuration % $increment
		) {
			throw new InvalidArgumentException( 'Booking durations must be valid 15-minute values within the working day.' );
		}
	}

	/**
	 * Create the initial company defaults.
	 */
	public static function defaults(): self {
		return new self( 540, 1080, 30, 60 );
	}
}
