<?php
/**
 * Booking lock timeout exception.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Exception;

use RuntimeException;

/**
 * Signals that another booking operation held the date lock too long.
 */
final class BookingLockTimeout extends RuntimeException {
}
