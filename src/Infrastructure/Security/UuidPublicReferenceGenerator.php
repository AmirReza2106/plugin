<?php
/**
 * WordPress UUID public reference generator.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Security;

use WorkshopRegistration\Application\Contracts\PublicReferenceGenerator;

/**
 * Generates UUID v4 request references through WordPress.
 */
final class UuidPublicReferenceGenerator implements PublicReferenceGenerator {
	/**
	 * Generate a public request reference.
	 */
	public function generate(): string {
		return wp_generate_uuid4();
	}
}
