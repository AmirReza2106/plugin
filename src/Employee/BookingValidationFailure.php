<?php
/**
 * Employee booking validation failure.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Employee;

use DomainException;

/**
 * Carries a safe allow-listed validation code.
 */
final class BookingValidationFailure extends DomainException {
}
