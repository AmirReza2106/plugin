<?php
/**
 * Workshop request status.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Domain;

/**
 * Defines all supported workshop request states.
 */
enum WorkshopStatus: string {
	case Pending  = 'pending';
	case Approved = 'approved';
	case Rejected = 'rejected';

	/**
	 * Determine whether this status reserves room capacity.
	 */
	public function reservesRoom(): bool {
		return self::Rejected !== $this;
	}
}
