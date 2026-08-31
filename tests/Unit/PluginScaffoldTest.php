<?php
/**
 * Plugin scaffold tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Plugin;

/**
 * Verifies the initial autoloadable plugin structure.
 */
final class PluginScaffoldTest extends TestCase {
	/**
	 * The plugin composition root is available through Composer.
	 */
	public function test_plugin_class_is_autoloadable(): void {
		self::assertTrue( class_exists( Plugin::class ) );
	}
}
