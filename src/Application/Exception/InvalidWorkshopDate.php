<?php
/**
 * Invalid workshop date exception.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Exception;

use DomainException;

/**
 * Identifies invalid dates without coupling the application to UI text.
 */
final class InvalidWorkshopDate extends DomainException {
	public const INVALID_FORMAT = 'invalid_format';

	public const INVALID_TIMEZONE = 'invalid_timezone';

	public const PAST_DATE = 'past_date';
}
