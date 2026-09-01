<?php
/**
 * Employee-owned booking list item.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\EmployeeDashboard;

use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Contains list-safe details for a request owned by the current employee.
 */
final class EmployeeBookingItem {
	/**
	 * Create an employee booking list item.
	 *
	 * @param int            $id              Internal request ID.
	 * @param string         $publicReference Public request reference.
	 * @param string         $workshopTitle   Meeting title.
	 * @param string         $workshopDate    Meeting date.
	 * @param string         $startTime       Start time.
	 * @param string         $endTime         End time.
	 * @param int|null       $slotNumber      Assigned room number.
	 * @param WorkshopStatus $status          Current request status.
	 * @param string         $createdAt       UTC creation timestamp.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $publicReference,
		public readonly string $workshopTitle,
		public readonly string $workshopDate,
		public readonly string $startTime,
		public readonly string $endTime,
		public readonly ?int $slotNumber,
		public readonly WorkshopStatus $status,
		public readonly string $createdAt
	) {
	}
}
