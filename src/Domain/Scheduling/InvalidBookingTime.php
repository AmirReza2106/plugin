<?php
/**
 * Invalid booking time exception.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

use DomainException;

/**
 * Identifies a booking-time validation failure without presentation text.
 */
final class InvalidBookingTime extends DomainException {
	public const INVALID_FORMAT = 'invalid_format';

	public const INVALID_INCREMENT = 'invalid_increment';

	public const OUTSIDE_WORKING_HOURS = 'outside_working_hours';

	public const INVALID_DURATION = 'invalid_duration';

	/**
	 * Machine-readable validation reason.
	 *
	 * @var string
	 */
	private string $reason;

	/**
	 * Create a booking-time validation exception.
	 *
	 * @param string $reason Machine-readable validation reason.
	 */
	public function __construct( string $reason ) {
		$this->reason = $reason;
		parent::__construct( $reason );
	}

	/**
	 * Get the machine-readable validation reason.
	 */
	public function reason(): string {
		return $this->reason;
	}
}
