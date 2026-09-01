<?php
/**
 * Public request reference generator contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

/**
 * Generates a non-sequential public request reference.
 */
interface PublicReferenceGenerator {
	/**
	 * Generate a public request reference.
	 */
	public function generate(): string;
}
