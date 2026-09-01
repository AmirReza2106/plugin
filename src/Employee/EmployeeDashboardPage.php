<?php
/**
 * Employee booking dashboard page.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Employee;

use DateTimeImmutable;
use WorkshopRegistration\Access\RoleManager;
use WorkshopRegistration\Application\Contracts\EmployeeBookingQuery;
use WorkshopRegistration\Application\Contracts\WorkshopRepository;
use WorkshopRegistration\Application\EmployeeDashboard\AvailabilityPeriod;
use WorkshopRegistration\Application\EmployeeDashboard\AvailabilityTimelineBuilder;
use WorkshopRegistration\Domain\Scheduling\SchedulingRules;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Infrastructure\Settings\SchedulingSettings;

/**
 * Renders company room availability and current employee request history.
 */
final class EmployeeDashboardPage {
	public const PAGE_SLUG = 'workshop-room-booking';

	private const REQUESTS_PER_PAGE = 10;

	/**
	 * Create the employee dashboard page.
	 *
	 * @param WorkshopRepository          $availability_repository Shared availability source.
	 * @param EmployeeBookingQuery        $employee_query          Employee-owned booking query.
	 * @param AvailabilityTimelineBuilder $timeline_builder        Privacy-safe timeline builder.
	 * @param SchedulingSettings          $settings                Scheduling settings source.
	 * @param EmployeeNoticeStore         $notice_store            One-time notice store.
	 */
	public function __construct(
		private WorkshopRepository $availability_repository,
		private EmployeeBookingQuery $employee_query,
		private AvailabilityTimelineBuilder $timeline_builder,
		private SchedulingSettings $settings,
		private EmployeeNoticeStore $notice_store
	) {
	}

	/**
	 * Register menu and page assets.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Add the employee-visible top-level booking menu.
	 */
	public function addMenu(): void {
		add_menu_page(
			'رزرو اتاق جلسه',
			'رزرو اتاق جلسه',
			RoleManager::VIEW_AVAILABILITY,
			self::PAGE_SLUG,
			array( $this, 'render' ),
			'dashicons-building',
			26
		);
	}

	/**
	 * Render the employee dashboard.
	 */
	public function render(): void {
		if ( ! current_user_can( RoleManager::VIEW_AVAILABILITY ) ) {
			wp_die( esc_html__( 'شما اجازه مشاهده برنامه اتاق‌ها را ندارید.', 'workshop-registration' ), 403 );
		}

		$selected_date = $this->selectedDate();
		$status        = $this->selectedStatus();
		$current_page  = $this->selectedPage();
		$rules         = $this->settings->rules();
		$capacity      = $this->settings->roomCapacity();
		$reservations  = $this->availability_repository->findActiveReservationsByDate( $selected_date );
		$rooms         = $this->timeline_builder->build( $rules, $capacity, $reservations );
		$bookings      = $this->employee_query->findPage(
			get_current_user_id(),
			$status,
			$current_page,
			self::REQUESTS_PER_PAGE
		);
		$notice        = $this->consumeNotice();
		$current_user  = wp_get_current_user();

		include plugin_dir_path( WORKSHOP_REGISTRATION_FILE ) . 'templates/employee-dashboard.php';
	}

	/**
	 * Load scoped dashboard assets only on this plugin page.
	 *
	 * @param string $hook_suffix Current WordPress admin hook.
	 */
	public function enqueueAssets( string $hook_suffix ): void {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'workshop-employee-dashboard',
			plugins_url( 'assets/css/employee-dashboard.css', WORKSHOP_REGISTRATION_FILE ),
			array(),
			WORKSHOP_REGISTRATION_VERSION
		);
		wp_enqueue_script(
			'workshop-employee-dashboard',
			plugins_url( 'assets/js/employee-dashboard.js', WORKSHOP_REGISTRATION_FILE ),
			array(),
			WORKSHOP_REGISTRATION_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Format minutes after midnight as HH:MM.
	 *
	 * @param int $minute Minutes after midnight.
	 */
	public function formatMinute( int $minute ): string {
		return sprintf( '%02d:%02d', intdiv( $minute, 60 ), $minute % 60 );
	}

	/**
	 * Build safe positioning declarations for one timeline period.
	 *
	 * @param AvailabilityPeriod $period Timeline period.
	 * @param SchedulingRules    $rules  Current scheduling rules.
	 */
	public function periodStyle( AvailabilityPeriod $period, SchedulingRules $rules ): string {
		$length = $rules->workdayEndMinute - $rules->workdayStartMinute;
		$start  = ( ( $period->startMinute - $rules->workdayStartMinute ) / $length ) * 100;
		$width  = ( ( $period->endMinute - $period->startMinute ) / $length ) * 100;

		return sprintf( '--period-start: %.4f%%; --period-width: %.4f%%;', $start, $width );
	}

	/**
	 * Get a Persian status label.
	 *
	 * @param WorkshopStatus $status Request status.
	 */
	public function statusLabel( WorkshopStatus $status ): string {
		return match ( $status ) {
			WorkshopStatus::Pending => 'در انتظار تأیید',
			WorkshopStatus::Approved => 'تأییدشده',
			WorkshopStatus::Rejected => 'ردشده',
		};
	}

	/**
	 * Map a safe error code to a Persian dashboard message.
	 *
	 * @param string $code Safe error code.
	 */
	public function errorMessage( string $code ): string {
		$messages = array(
			'invalid_request'   => 'روش ارسال درخواست معتبر نیست.',
			'request_too_large' => 'حجم اطلاعات ارسال‌شده بیش از حد مجاز است.',
			'expired_form'      => 'اعتبار فرم پایان یافته است. لطفاً دوباره تلاش کنید.',
			'missing_fields'    => 'لطفاً همه فیلدهای الزامی را تکمیل کنید.',
			'input_too_long'    => 'طول مقدار یک یا چند فیلد بیش از حد مجاز است.',
			'invalid_input'     => 'اطلاعات واردشده را بررسی و دوباره تلاش کنید.',
			'invalid_email'     => 'لطفاً یک ایمیل معتبر وارد کنید.',
			'invalid_mobile'    => 'لطفاً یک شماره موبایل معتبر وارد کنید.',
			'invalid_schedule'  => 'تاریخ، ساعت یا مدت جلسه با قوانین رزرو سازگار نیست.',
			'no_room'           => 'در بازه انتخاب‌شده اتاقی در دسترس نیست.',
			'booking_busy'      => 'رزروهای این تاریخ در حال به‌روزرسانی است. لطفاً کمی بعد دوباره تلاش کنید.',
			'temporary_error'   => 'ثبت درخواست انجام نشد. لطفاً دوباره تلاش کنید.',
		);

		return $messages[ $code ] ?? 'درخواست انجام نشد. لطفاً دوباره تلاش کنید.';
	}

	/**
	 * Read a strict selected availability date.
	 */
	private function selectedDate(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET['booking_date'] ) && is_string( $_GET['booking_date'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET['booking_date'] ) )
			: '';
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );

		if ( false === $date || $date->format( 'Y-m-d' ) !== $value ) {
			return (string) wp_date( 'Y-m-d', null, wp_timezone() );
		}

		return (string) $date->format( 'Y-m-d' );
	}

	/**
	 * Read an allow-listed request status filter.
	 */
	private function selectedStatus(): ?WorkshopStatus {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET['booking_status'] ) && is_string( $_GET['booking_status'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_GET['booking_status'] ) )
			: '';

		return WorkshopStatus::tryFrom( $value );
	}

	/**
	 * Read a bounded one-based request page number.
	 */
	private function selectedPage(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return max( 1, min( 100000, absint( $_GET['booking_page'] ?? 1 ) ) );
	}

	/**
	 * Consume a one-time notice owned by the current employee.
	 *
	 * @return array<string, mixed>|null
	 */
	private function consumeNotice(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$receipt = isset( $_GET['booking_notice'] ) && is_string( $_GET['booking_notice'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET['booking_notice'] ) )
			: '';

		return '' === $receipt ? null : $this->notice_store->consume( get_current_user_id(), $receipt );
	}
}
