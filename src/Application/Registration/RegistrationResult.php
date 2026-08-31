<?php
/**
 * Successful workshop registration result.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Registration;

use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Returns the assigned room and one-time private tracking credential.
 */
final class RegistrationResult {
	/**
	 * Create a registration result.
	 *
	 * @param int            $requestId       Request database ID.
	 * @param string         $publicReference Public UUID reference.
	 * @param string         $trackingToken   Raw one-time tracking token.
	 * @param int            $slotNumber      Assigned room slot.
	 * @param WorkshopStatus $status          Initial request status.
	 */
	public function __construct(
		public readonly int $requestId,
		public readonly string $publicReference,
		public readonly string $trackingToken,
		public readonly int $slotNumber,
		public readonly WorkshopStatus $status
	) {
	}
}
