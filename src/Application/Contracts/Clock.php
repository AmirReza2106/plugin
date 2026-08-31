<?php
/**
 * Application clock contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Supplies the current time without coupling use cases to the system clock.
 */
interface Clock {
	/**
	 * Get the current immutable time in a timezone.
	 *
	 * @param DateTimeZone $timezone Requested timezone.
	 */
	public function now( DateTimeZone $timezone ): DateTimeImmutable;
}
