<?php
/**
 * Booking interval value object.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain\Scheduling;

use InvalidArgumentException;

/**
 * Represents a half-open interval as minutes after midnight.
 */
final class BookingInterval {
	/**
	 * Start minute after midnight.
	 *
	 * @var int
	 */
	private int $start_minute;

	/**
	 * End minute after midnight.
	 *
	 * @var int
	 */
	private int $end_minute;

	/**
	 * Create a valid ordered interval.
	 *
	 * @param int $start_minute Start minute after midnight.
	 * @param int $end_minute   End minute after midnight.
	 */
	private function __construct( int $start_minute, int $end_minute ) {
		$this->start_minute = $start_minute;
		$this->end_minute   = $end_minute;
	}

	/**
	 * Create an interval from validated minute values.
	 *
	 * The scheduling policy applies business constraints before calling this.
	 *
	 * @param int $start_minute Start minute after midnight.
	 * @param int $end_minute   End minute after midnight.
	 * @throws InvalidArgumentException When the interval is invalid.
	 */
	public static function fromMinutes( int $start_minute, int $end_minute ): self {
		if ( $start_minute < 0 || $end_minute > 1440 || $start_minute >= $end_minute ) {
			throw new InvalidArgumentException( 'A booking interval must be ordered within one day.' );
		}

		return new self( $start_minute, $end_minute );
	}

	/**
	 * Get the start minute after midnight.
	 */
	public function startMinute(): int {
		return $this->start_minute;
	}

	/**
	 * Get the end minute after midnight.
	 */
	public function endMinute(): int {
		return $this->end_minute;
	}

	/**
	 * Determine whether two half-open intervals overlap.
	 *
	 * @param self $other Interval to compare.
	 */
	public function conflictsWith( self $other ): bool {
		return $this->start_minute < $other->end_minute
			&& $this->end_minute > $other->start_minute;
	}
}
