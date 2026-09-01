<?php
/**
 * Administrator request list item.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\AdminRequests;

use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Contains complete booking details for authorized administrators.
 */
final class AdminRequestItem {
	/**
	 * Create an administrator request projection.
	 *
	 * @param int            $id              Request ID.
	 * @param string         $publicReference Public request reference.
	 * @param int            $requesterUserId Owning WordPress user ID.
	 * @param string         $firstName       Submitted first name.
	 * @param string         $lastName        Submitted last name.
	 * @param string         $mobile          Submitted mobile number.
	 * @param string         $email           Submitted email address.
	 * @param string         $workshopTitle   Meeting title.
	 * @param string         $workshopDate    Local meeting date.
	 * @param string         $startTime       Local start time.
	 * @param string         $endTime         Local end time.
	 * @param string         $description     Meeting description.
	 * @param WorkshopStatus $status          Current status.
	 * @param int|null       $slotNumber      Current room number.
	 * @param int|null       $reviewedBy      Reviewing administrator ID.
	 * @param string|null    $statusChangedAt UTC decision timestamp.
	 * @param string         $createdAt       UTC creation timestamp.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $publicReference,
		public readonly int $requesterUserId,
		public readonly string $firstName,
		public readonly string $lastName,
		public readonly string $mobile,
		public readonly string $email,
		public readonly string $workshopTitle,
		public readonly string $workshopDate,
		public readonly string $startTime,
		public readonly string $endTime,
		public readonly string $description,
		public readonly WorkshopStatus $status,
		public readonly ?int $slotNumber,
		public readonly ?int $reviewedBy,
		public readonly ?string $statusChangedAt,
		public readonly string $createdAt
	) {
	}
}
