<?php
/**
 * Administrator scheduling settings page.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Admin;

use Throwable;
use WorkshopRegistration\Domain\Scheduling\SchedulingRules;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Infrastructure\Settings\SchedulingSettings;
use wpdb;

/**
 * Registers and renders validated room scheduling settings.
 */
final class SchedulingSettingsPage {
	private const PAGE_SLUG = 'workshop-registration-settings';

	private const SETTINGS_GROUP = 'workshop_registration_settings_group';

	/**
	 * Create the settings page.
	 *
	 * @param wpdb               $database WordPress database connection.
	 * @param Tables             $tables   Plugin table names.
	 * @param SchedulingSettings $settings Scheduling settings storage.
	 */
	public function __construct(
		private wpdb $database,
		private Tables $tables,
		private SchedulingSettings $settings
	) {
	}

	/**
	 * Register WordPress settings hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenu' ) );
		add_action( 'admin_init', array( $this, 'registerSetting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Add the Persian settings page under WordPress Settings.
	 */
	public function addMenu(): void {
		add_options_page(
			'تنظیمات رزرو اتاق جلسه',
			'رزرو اتاق جلسه',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Register the structured non-autoloaded settings option.
	 */
	public function registerSetting(): void {
		register_setting(
			self::SETTINGS_GROUP,
			SchedulingSettings::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => SchedulingSettings::defaults(),
			)
		);
	}

	/**
	 * Validate a complete settings update.
	 *
	 * @param mixed $input Raw Settings API value.
	 * @return array{workday_start: string, workday_end: string, minimum_duration: int, maximum_duration: int, room_capacity: int}
	 */
	public function sanitize( mixed $input ): array {
		$current = $this->settings->all();

		if ( ! is_array( $input ) ) {
			$this->addError( 'مقادیر تنظیمات معتبر نیستند.' );
			return $current;
		}

		$candidate = array(
			'workday_start'    => isset( $input['workday_start'] ) && is_string( $input['workday_start'] ) ? sanitize_text_field( $input['workday_start'] ) : '',
			'workday_end'      => isset( $input['workday_end'] ) && is_string( $input['workday_end'] ) ? sanitize_text_field( $input['workday_end'] ) : '',
			'minimum_duration' => isset( $input['minimum_duration'] ) ? (int) $input['minimum_duration'] : 0,
			'maximum_duration' => isset( $input['maximum_duration'] ) ? (int) $input['maximum_duration'] : 0,
			'room_capacity'    => isset( $input['room_capacity'] ) ? (int) $input['room_capacity'] : 0,
		);

		try {
			new SchedulingRules(
				$this->timeToMinute( $candidate['workday_start'] ),
				$this->timeToMinute( $candidate['workday_end'] ),
				$candidate['minimum_duration'],
				$candidate['maximum_duration']
			);
		} catch ( Throwable ) {
			$this->addError( 'ساعات کاری و مدت رزرو باید معتبر و بر اساس بازه‌های ۱۵ دقیقه‌ای باشند.' );
			return $current;
		}

		if ( $candidate['room_capacity'] < 1 || $candidate['room_capacity'] > 100 ) {
			$this->addError( 'تعداد اتاق‌ها باید بین ۱ تا ۱۰۰ باشد.' );
			return $current;
		}

		if ( $candidate['room_capacity'] < $this->highestActiveFutureSlot() ) {
			$this->addError( 'تعداد اتاق‌ها کمتر از شماره اتاق رزروهای فعال آینده است.' );
			return $current;
		}

		add_settings_error(
			SchedulingSettings::OPTION_NAME,
			'workshop_settings_saved',
			'تنظیمات رزرو اتاق با موفقیت ذخیره شد.',
			'success'
		);

		return $candidate;
	}

	/**
	 * Render the protected settings form.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings->all();
		$option   = SchedulingSettings::OPTION_NAME;
		?>
		<div class="wrap workshop-settings" dir="rtl">
			<div class="workshop-settings__header">
				<h1>تنظیمات رزرو اتاق جلسه</h1>
				<p>قوانین زمانی و ظرفیت اتاق‌های قابل رزرو را مدیریت کنید.</p>
			</div>
			<?php settings_errors( SchedulingSettings::OPTION_NAME ); ?>
			<form method="post" action="options.php" class="workshop-settings__card">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<div class="workshop-settings__grid">
					<label>
						<span>شروع ساعت کاری</span>
						<input type="time" name="<?php echo esc_attr( $option ); ?>[workday_start]" value="<?php echo esc_attr( $settings['workday_start'] ); ?>" step="900" required>
					</label>
					<label>
						<span>پایان ساعت کاری</span>
						<input type="time" name="<?php echo esc_attr( $option ); ?>[workday_end]" value="<?php echo esc_attr( $settings['workday_end'] ); ?>" step="900" required>
					</label>
					<label>
						<span>حداقل مدت رزرو (دقیقه)</span>
						<input type="number" name="<?php echo esc_attr( $option ); ?>[minimum_duration]" value="<?php echo esc_attr( (string) $settings['minimum_duration'] ); ?>" min="15" step="15" required>
					</label>
					<label>
						<span>حداکثر مدت رزرو (دقیقه)</span>
						<input type="number" name="<?php echo esc_attr( $option ); ?>[maximum_duration]" value="<?php echo esc_attr( (string) $settings['maximum_duration'] ); ?>" min="15" step="15" required>
					</label>
					<label>
						<span>تعداد اتاق‌ها</span>
						<input type="number" name="<?php echo esc_attr( $option ); ?>[room_capacity]" value="<?php echo esc_attr( (string) $settings['room_capacity'] ); ?>" min="1" max="100" required>
					</label>
				</div>
				<div class="workshop-settings__note">
					<strong>قانون زمان‌بندی:</strong>
					شروع و پایان رزرو با فاصله‌های ۱۵ دقیقه‌ای انتخاب می‌شود و بین دو جلسه هیچ فاصله اجباری وجود ندارد.
				</div>
				<?php submit_button( 'ذخیره تنظیمات' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue scoped styles only on this page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueueAssets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'workshop-registration-settings',
			plugins_url( 'assets/css/admin-settings.css', WORKSHOP_REGISTRATION_FILE ),
			array(),
			WORKSHOP_REGISTRATION_VERSION
		);
	}

	/**
	 * Get the highest numbered room occupied by an active future request.
	 */
	private function highestActiveFutureSlot(): int {
		$table = $this->tables->requests();
		$query = $this->database->prepare(
			'SELECT MAX(slot_number) FROM %i WHERE workshop_date >= %s AND status IN (%s, %s)',
			$table,
			current_time( 'Y-m-d' ),
			'pending',
			'approved'
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->database->get_var( $query );
	}

	/**
	 * Add a Persian Settings API error.
	 *
	 * @param string $message Safe administrator-facing message.
	 */
	private function addError( string $message ): void {
		add_settings_error( SchedulingSettings::OPTION_NAME, 'workshop_settings_invalid', $message, 'error' );
	}

	/**
	 * Convert a strict HH:MM setting into minutes.
	 *
	 * @param string $time Submitted setting.
	 * @throws \InvalidArgumentException When the value is invalid.
	 */
	private function timeToMinute( string $time ): int {
		if ( 1 !== preg_match( '/\A(?:[01]\d|2[0-3]):[0-5]\d\z/', $time ) ) {
			throw new \InvalidArgumentException( 'Invalid scheduling time.' );
		}

		$parts = array_map( 'intval', explode( ':', $time ) );

		return ( $parts[0] * 60 ) + $parts[1];
	}
}
