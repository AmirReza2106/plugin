<?php
/**
 * Administrator request query contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

use WorkshopRegistration\Application\AdminRequests\AdminRequestPage;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Reads administrator-visible booking requests with bounded filters.
 */
interface AdminRequestQuery {
	/**
	 * Find one filtered request page.
	 *
	 * @param WorkshopStatus|null $status   Optional status filter.
	 * @param string|null         $date     Optional local meeting date.
	 * @param string              $search   Bounded search text.
	 * @param int                 $page     One-based page number.
	 * @param int                 $per_page Page size.
	 */
	public function findPage(
		?WorkshopStatus $status,
		?string $date,
		string $search,
		int $page,
		int $per_page
	): AdminRequestPage;
}
