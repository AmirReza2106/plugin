<?php
/**
 * Main plugin composition root.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration;

use Throwable;
use WorkshopRegistration\Access\RoleManager;
use WorkshopRegistration\Admin\AdminRequestDecisionHandler;
use WorkshopRegistration\Admin\AdminRequestsPage;
use WorkshopRegistration\Admin\SchedulingSettingsPage;
use WorkshopRegistration\Application\AdminRequests\DecideRequest;
use WorkshopRegistration\Application\EmployeeDashboard\AvailabilityTimelineBuilder;
use WorkshopRegistration\Application\Registration\RegistrationServiceFactory;
use WorkshopRegistration\Employee\BookingInputValidator;
use WorkshopRegistration\Employee\EmployeeBookingHandler;
use WorkshopRegistration\Employee\EmployeeDashboardPage;
use WorkshopRegistration\Employee\EmployeeNoticeStore;
use WorkshopRegistration\Infrastructure\Database\Migrator;
use WorkshopRegistration\Infrastructure\Database\Schema;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Infrastructure\Database\WordPressAdminRequestQuery;
use WorkshopRegistration\Infrastructure\Database\WordPressAdminStatusHistoryQuery;
use WorkshopRegistration\Infrastructure\Database\WordPressEmployeeBookingQuery;
use WorkshopRegistration\Infrastructure\Database\WordPressRequestDecisionGateway;
use WorkshopRegistration\Infrastructure\Database\WordPressWorkshopRepository;
use WorkshopRegistration\Infrastructure\Settings\SchedulingSettings;
use WorkshopRegistration\Infrastructure\Time\SystemClock;
use WorkshopRegistration\Privacy\PersonalDataPrivacy;

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
		add_action( 'plugins_loaded', array( self::class, 'maybeProvisionAccess' ), 6 );
		add_action( 'init', array( self::class, 'loadTextDomain' ) );
		self::registerAdmin();
		self::registerEmployeeDashboard();
		self::registerPrivacy();
	}

	/**
	 * Apply the current schema during plugin activation.
	 */
	public static function activate(): void {
		self::migrator()->migrate();
		( new RoleManager() )->install();
		( new SchedulingSettings() )->installDefaults();
	}

	/**
	 * Provision role capabilities and default settings after updates.
	 */
	public static function maybeProvisionAccess(): void {
		( new RoleManager() )->maybeInstall();
		( new SchedulingSettings() )->installDefaults();
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
			<p><?php esc_html_e( 'به‌روزرسانی پایگاه داده افزونه انجام نشد. گزارش خطاهای وردپرس را بررسی و دوباره تلاش کنید.', 'workshop-registration' ); ?></p>
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

	/**
	 * Register administrator-only configuration hooks.
	 */
	private static function registerAdmin(): void {
		global $wpdb;

		( new SchedulingSettingsPage(
			$wpdb,
			new Tables( $wpdb->prefix ),
			new SchedulingSettings()
		) )->register();

		$tables = new Tables( $wpdb->prefix );
		( new AdminRequestsPage(
			new WordPressAdminRequestQuery( $wpdb, $tables ),
			new WordPressAdminStatusHistoryQuery( $wpdb, $tables )
		) )->register();
		( new AdminRequestDecisionHandler(
			new DecideRequest(
				new WordPressRequestDecisionGateway( $wpdb, $tables ),
				new SystemClock()
			)
		) )->register();
	}

	/**
	 * Register authenticated employee dashboard and submission hooks.
	 */
	private static function registerEmployeeDashboard(): void {
		global $wpdb;

		$tables       = new Tables( $wpdb->prefix );
		$settings     = new SchedulingSettings();
		$notice_store = new EmployeeNoticeStore();

		( new EmployeeDashboardPage(
			new WordPressWorkshopRepository( $wpdb, $tables ),
			new WordPressEmployeeBookingQuery( $wpdb, $tables ),
			new AvailabilityTimelineBuilder(),
			$settings,
			$notice_store
		) )->register();

		( new EmployeeBookingHandler(
			new RegistrationServiceFactory( $wpdb, $settings ),
			$settings,
			new BookingInputValidator(),
			$notice_store
		) )->register();
	}

	/**
	 * Register WordPress personal-data privacy integration.
	 */
	private static function registerPrivacy(): void {
		global $wpdb;

		( new PersonalDataPrivacy(
			$wpdb,
			new Tables( $wpdb->prefix )
		) )->register();
	}
}
