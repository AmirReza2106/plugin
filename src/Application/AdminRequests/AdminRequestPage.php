<?php
/**
 * Paginated administrator request projection.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\AdminRequests;

/**
 * Contains one filtered page and global status counters.
 */
final class AdminRequestPage {
	/**
	 * Create an administrator request page.
	 *
	 * @param array $items        Current page items.
	 * @param int   $totalItems   Filtered item count.
	 * @param int   $currentPage  One-based page number.
	 * @param int   $perPage      Page size.
	 * @param array $statusCounts Global status counters.
	 * @phpstan-param list<AdminRequestItem> $items
	 * @phpstan-param array{pending:int,approved:int,rejected:int} $statusCounts
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
