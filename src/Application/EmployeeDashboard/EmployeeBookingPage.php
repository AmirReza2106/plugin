<?php
/**
 * Paginated employee booking projection.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\EmployeeDashboard;

/**
 * Contains one result page and status counters for an employee.
 */
final class EmployeeBookingPage {
	/**
	 * Create a paginated employee request result.
	 *
	 * @param array                                        $items        Current page items.
	 * @param int                                          $totalItems   Filtered result count.
	 * @param int                                          $currentPage  One-based page number.
	 * @param int                                          $perPage      Page size.
	 * @param array{pending:int,approved:int,rejected:int} $statusCounts Status counters.
	 * @phpstan-param list<EmployeeBookingItem> $items
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $totalItems,
		public readonly int $currentPage,
		public readonly int $perPage,
		public readonly array $statusCounts
	) {
	}

	/**
	 * Get total filtered pages.
	 */
	public function totalPages(): int {
		return max( 1, (int) ceil( $this->totalItems / $this->perPage ) );
	}
}
