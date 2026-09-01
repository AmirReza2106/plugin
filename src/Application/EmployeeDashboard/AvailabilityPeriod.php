<?php
/**
 * Availability timeline period.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\EmployeeDashboard;

use InvalidArgumentException;

/**
 * Represents one ordered period in minutes after midnight.
 */
final class AvailabilityPeriod {
	/**
	 * Create an ordered timeline period.
	 *
	 * @param int $startMinute Start minute after midnight.
	 * @param int $endMinute   End minute after midnight.
	 * @throws InvalidArgumentException When the period is invalid.
	 */
	public function __construct(
		public readonly int $startMinute,
		public readonly int $endMinute
	) {
		if ( $startMinute < 0 || $endMinute > 1440 || $startMinute >= $endMinute ) {
			throw new InvalidArgumentException( 'An availability period must be ordered within one day.' );
		}
	}
}
