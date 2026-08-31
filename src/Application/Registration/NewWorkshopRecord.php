<?php
/**
 * New workshop persistence record.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Registration;

/**
 * Contains only fields permitted to cross the persistence boundary.
 */
final class NewWorkshopRecord {
	/**
	 * Create a pending workshop persistence record.
	 *
	 * @param RegistrationData $registration      Normalized registration data.
	 * @param string           $publicReference    Public UUID reference.
	 * @param string           $trackingTokenHash SHA-256 tracking token hash.
	 * @param int              $slotNumber         Assigned room slot.
	 * @param string           $createdAt          UTC database timestamp.
	 */
	public function __construct(
		public readonly RegistrationData $registration,
		public readonly string $publicReference,
		public readonly string $trackingTokenHash,
		public readonly int $slotNumber,
		public readonly string $createdAt
	) {
	}
}
