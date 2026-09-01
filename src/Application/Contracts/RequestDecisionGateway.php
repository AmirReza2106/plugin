<?php
/**
 * Request decision persistence contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Atomically finalizes pending requests and records their history.
 */
interface RequestDecisionGateway {
	/**
	 * Apply one final administrator decision.
	 *
	 * @param int            $request_id   Request database ID.
	 * @param WorkshopStatus $target        Approved or rejected target status.
	 * @param int            $actor_user_id Administrator user ID.
	 * @param string         $changed_at    UTC database timestamp.
	 */
	public function decide( int $request_id, WorkshopStatus $target, int $actor_user_id, string $changed_at ): void;
}
