<?php
/**
 * Workshop registration input data.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Registration;

/**
 * Carries normalized registration fields into the application service.
 */
final class RegistrationData {
	/**
	 * Create registration input data.
	 *
	 * @param string $firstName        Requester's first name.
	 * @param string $lastName         Requester's last name.
	 * @param string $mobile           Display mobile number.
	 * @param string $mobileNormalized Searchable normalized mobile number.
	 * @param string $email            Requester's email address.
	 * @param string $workshopTitle    Workshop title.
	 * @param string $workshopDate     Local workshop date.
	 * @param string $startTime        Local start time.
	 * @param string $endTime          Local end time.
	 * @param string $siteTimezone     Site timezone at registration.
	 * @param string $description      Workshop description.
	 */
	public function __construct(
		public readonly string $firstName,
		public readonly string $lastName,
		public readonly string $mobile,
		public readonly string $mobileNormalized,
		public readonly string $email,
		public readonly string $workshopTitle,
		public readonly string $workshopDate,
		public readonly string $startTime,
		public readonly string $endTime,
		public readonly string $siteTimezone,
		public readonly string $description
	) {
	}
}
