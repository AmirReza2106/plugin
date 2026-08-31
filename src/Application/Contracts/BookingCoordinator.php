<?php
/**
 * Atomic booking coordinator contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

/**
 * Serializes and transacts booking changes for one date.
 */
interface BookingCoordinator {
	/**
	 * Execute one operation under a date lock and database transaction.
	 *
	 * @template T
	 * @param string       $workshop_date Workshop date in Y-m-d format.
	 * @param callable():T $operation    Operation to execute atomically.
	 * @return T
	 */
	public function run( string $workshop_date, callable $operation ): mixed;
}
