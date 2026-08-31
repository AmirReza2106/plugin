<?php
/**
 * Main plugin composition root.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration;

use Throwable;
use WorkshopRegistration\Infrastructure\Database\Migrator;
use WorkshopRegistration\Infrastructure\Database\Schema;
use WorkshopRegistration\Infrastructure\Database\Tables;

/**
 * Registers the plugin with WordPress.
 */
final class Plugin {
	/**
	 * Whether the database migration failed during this request.
	 *
	 * @var bool
	 */
	private static bool $migration_failed = false;

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {
	}

	/**
	 * Register WordPress hooks.
	 */
	public static function boot(): void {
		register_activation_hook( WORKSHOP_REGISTRATION_FILE, array( self::class, 'activate' ) );
		add_action( 'plugins_loaded', array( self::class, 'maybeMigrate' ), 5 );
		add_action( 'init', array( self::class, 'loadTextDomain' ) );
	}

	/**
	 * Apply the current schema during plugin activation.
	 */
	public static function activate(): void {
		self::migrator()->migrate();
	}

	/**
	 * Apply schema changes after a plugin update.
	 */
	public static function maybeMigrate(): void {
		try {
			self::migrator()->migrate();
		} catch ( Throwable $exception ) {
			self::$migration_failed = true;
			// Schema errors contain no request data and belong in private server logs.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Workshop Registration database migration failed: ' . $exception->getMessage() );
			add_action( 'admin_notices', array( self::class, 'renderMigrationNotice' ) );
		}
	}

	/**
	 * Render a migration failure notice to authorized administrators.
	 */
	public static function renderMigrationNotice(): void {
		if ( ! current_user_can( 'manage_options' ) || ! self::$migration_failed ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Workshop Registration could not update its database. Check the WordPress error log and try again.', 'workshop-registration' ); ?></p>
		</div>
		<?php
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

	/**
	 * Build the database migration service.
	 */
	private static function migrator(): Migrator {
		global $wpdb;

		return new Migrator(
			$wpdb,
			new Schema(),
			new Tables( $wpdb->prefix )
		);
	}
}
