<?php
/**
 * Employee booking query contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

use WorkshopRegistration\Application\EmployeeDashboard\EmployeeBookingPage;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Reads only bookings owned by one authenticated employee.
 */
interface EmployeeBookingQuery {
	/**
	 * Find one page of employee-owned requests.
	 *
	 * @param int                 $employee_user_id Authenticated employee ID.
	 * @param WorkshopStatus|null $status           Optional status filter.
	 * @param int                 $page             One-based page number.
	 * @param int                 $per_page         Page size.
	 */
	public function findPage(
		int $employee_user_id,
		?WorkshopStatus $status,
		int $page,
		int $per_page
	): EmployeeBookingPage;
}
