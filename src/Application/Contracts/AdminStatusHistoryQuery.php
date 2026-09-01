<?php
/**
 * Administrator status history query contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

use WorkshopRegistration\Application\AdminRequests\AdminStatusHistoryItem;

/**
 * Reads audit history only for administrator-visible request IDs.
 */
interface AdminStatusHistoryQuery {
	/**
	 * Find status events grouped by request ID.
	 *
	 * @param array $request_ids Request database IDs.
	 * @return array<int, list<AdminStatusHistoryItem>>
	 * @phpstan-param list<int> $request_ids
	 */
	public function findByRequestIds( array $request_ids ): array;
}
