<?php
/**
 * System clock implementation.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Time;

use DateTimeImmutable;
use DateTimeZone;
use WorkshopRegistration\Application\Contracts\Clock;

/**
 * Supplies the real current time.
 */
final class SystemClock implements Clock {
	/**
	 * Get the current immutable time in a timezone.
	 *
	 * @param DateTimeZone $timezone Requested timezone.
	 */
	public function now( DateTimeZone $timezone ): DateTimeImmutable {
		return new DateTimeImmutable( 'now', $timezone );
	}
}
