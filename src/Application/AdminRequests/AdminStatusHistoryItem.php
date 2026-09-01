<?php
/**
 * Administrator status history projection.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\AdminRequests;

use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Contains one immutable request audit event.
 */
final class AdminStatusHistoryItem {
	/**
	 * Create a status history projection.
	 *
	 * @param int                 $requestId         Request database ID.
	 * @param WorkshopStatus|null $fromStatus        Previous status for non-initial events.
	 * @param WorkshopStatus      $toStatus          New status.
	 * @param int|null            $previousSlot      Previous room number.
	 * @param int|null            $newSlot           New room number.
	 * @param int|null            $actorUserId       Administrator ID for decisions.
	 * @param string|null         $actorDisplayName  Administrator display name.
	 * @param string              $createdAt          UTC event timestamp.
	 */
	public function __construct(
		public readonly int $requestId,
		public readonly ?WorkshopStatus $fromStatus,
		public readonly WorkshopStatus $toStatus,
		public readonly ?int $previousSlot,
		public readonly ?int $newSlot,
		public readonly ?int $actorUserId,
		public readonly ?string $actorDisplayName,
		public readonly string $createdAt
	) {
	}
}
