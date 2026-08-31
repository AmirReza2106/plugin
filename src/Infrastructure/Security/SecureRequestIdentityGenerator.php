<?php
/**
 * Secure request identity generator.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Security;

use WorkshopRegistration\Application\Contracts\RequestIdentityGenerator;
use WorkshopRegistration\Application\Registration\RequestIdentity;

/**
 * Generates an unguessable tracking token and stores only its hash.
 */
final class SecureRequestIdentityGenerator implements RequestIdentityGenerator {
	/**
	 * Generate a new request identity.
	 */
	public function generate(): RequestIdentity {
		$tracking_token = bin2hex( random_bytes( 32 ) );

		return new RequestIdentity(
			wp_generate_uuid4(),
			$tracking_token,
			hash( 'sha256', $tracking_token )
		);
	}
}
