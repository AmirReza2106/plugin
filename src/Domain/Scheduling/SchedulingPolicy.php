<?php
/**
 * Company booking-time policy.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

/**
 * Validates booking times against configurable company scheduling rules.
 */
final class SchedulingPolicy {
	/**
	 * Active company scheduling rules.
	 *
	 * @var SchedulingRules
	 */
	private SchedulingRules $rules;

	/**
	 * Create a policy with configured rules or company defaults.
	 *
	 * @param SchedulingRules|null $rules Configured scheduling rules.
	 */
	public function __construct( ?SchedulingRules $rules = null ) {
		$this->rules = $rules ?? SchedulingRules::defaults();
	}

	/**
	 * Validate submitted times and create their booking interval.
	 *
	 * @param string $start_time Strict 24-hour start time.
	 * @param string $end_time   Strict 24-hour end time.
	 * @throws InvalidBookingTime When either time violates the booking policy.
	 */
	public function createInterval( string $start_time, string $end_time ): BookingInterval {
		$start_minute = $this->parseTime( $start_time );
		$end_minute   = $this->parseTime( $end_time );

		if ( 0 !== $start_minute % SchedulingRules::TIME_INCREMENT_MINUTES || 0 !== $end_minute % SchedulingRules::TIME_INCREMENT_MINUTES ) {
			throw new InvalidBookingTime( InvalidBookingTime::INVALID_INCREMENT );
		}

		if ( $start_minute < $this->rules->workdayStartMinute || $end_minute > $this->rules->workdayEndMinute ) {
			throw new InvalidBookingTime( InvalidBookingTime::OUTSIDE_WORKING_HOURS );
		}

		$duration = $end_minute - $start_minute;

		if ( $duration < $this->rules->minimumDuration || $duration > $this->rules->maximumDuration ) {
			throw new InvalidBookingTime( InvalidBookingTime::INVALID_DURATION );
		}

		return BookingInterval::fromMinutes( $start_minute, $end_minute );
	}

	/**
	 * Parse a strict 24-hour HH:MM value into minutes after midnight.
	 *
	 * @param string $time Submitted time.
	 * @throws InvalidBookingTime When the value is not a strict time.
	 */
	private function parseTime( string $time ): int {
		if ( 1 !== preg_match( '/\A(?:[01]\d|2[0-3]):[0-5]\d\z/', $time ) ) {
			throw new InvalidBookingTime( InvalidBookingTime::INVALID_FORMAT );
		}

		$parts = array_map( 'intval', explode( ':', $time ) );

		return ( $parts[0] * 60 ) + $parts[1];
	}
}
