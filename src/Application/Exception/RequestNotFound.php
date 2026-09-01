<?php
/**
 * Missing booking request exception.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Exception;

use RuntimeException;

/**
 * Raised when an administrator targets a request that does not exist.
 */
final class RequestNotFound extends RuntimeException {
}
