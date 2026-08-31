<?php
/**
 * Workshop request identity.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Registration;

use InvalidArgumentException;

/**
 * Separates the one-time tracking token from its persisted hash.
 */
final class RequestIdentity {
	/**
	 * Create a secure request identity.
	 *
	 * @param string $publicReference Public UUID reference.
	 * @param string $trackingToken   Raw private token returned once.
	 * @param string $tokenHash       SHA-256 token hash for persistence.
	 * @throws InvalidArgumentException When any identity component is invalid.
	 */
	public function __construct(
		public readonly string $publicReference,
		public readonly string $trackingToken,
		public readonly string $tokenHash
	) {
		if ( '' === $publicReference || '' === $trackingToken || 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $tokenHash ) ) {
			throw new InvalidArgumentException( 'The request identity is invalid.' );
		}
	}
}
