<?php
/**
 * Main plugin composition root.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration;

/**
 * Registers the plugin with WordPress.
 */
final class Plugin {
	/**
	 * Prevent direct construction.
	 */
	private function __construct() {
	}

	/**
	 * Register WordPress hooks.
	 */
	public static function boot(): void {
		add_action( 'init', array( self::class, 'loadTextDomain' ) );
	}

	/**
	 * Load translations from the plugin languages directory.
	 */
	public static function loadTextDomain(): void {
		load_plugin_textdomain(
			'workshop-registration',
			false,
			dirname( plugin_basename( WORKSHOP_REGISTRATION_FILE ) ) . '/languages'
		);
	}
}
