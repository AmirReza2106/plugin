<?php
/**
 * Request identity generator contract.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Contracts;

use WorkshopRegistration\Application\Registration\RequestIdentity;

/**
 * Generates public references and private tracking credentials.
 */
interface RequestIdentityGenerator {
	/**
	 * Generate a new request identity.
	 */
	public function generate(): RequestIdentity;
}
