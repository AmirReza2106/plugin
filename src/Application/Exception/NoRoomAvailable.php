<?php
/**
 * Room capacity exception.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Exception;

use RuntimeException;

/**
 * Signals that all stable room assignments conflict with a request.
 */
final class NoRoomAvailable extends RuntimeException {
}
