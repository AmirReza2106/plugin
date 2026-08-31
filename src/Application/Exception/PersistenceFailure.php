<?php
/**
 * Persistence failure exception.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Exception;

use RuntimeException;

/**
 * Provides a safe application boundary for database failures.
 */
final class PersistenceFailure extends RuntimeException {
}
