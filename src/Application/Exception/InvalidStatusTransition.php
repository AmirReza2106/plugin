<?php
/**
 * Invalid status transition exception.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Exception;

use RuntimeException;

/**
 * Raised when a request is no longer pending.
 */
final class InvalidStatusTransition extends RuntimeException {
}
