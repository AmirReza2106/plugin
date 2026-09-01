<?php
/**
 * Administrator request management page.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Admin;

use DateTimeImmutable;
use WorkshopRegistration\Access\RoleManager;
use WorkshopRegistration\Application\Contracts\AdminRequestQuery;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Employee\EmployeeDashboardPage;

/**
 * Renders filters, request details, and final decision controls.
 */
final class AdminRequestsPage {
	public const PAGE_SLUG = 'workshop-admin-requests';

	private const REQUESTS_PER_PAGE = 20;

	/**
	 * WordPress-generated page hook used for scoped assets.
	 *
	 * @var string|false|null
	 */
	private string|false|null $hook_suffix = null;

	/**
	 * Create the administrator page.
	 *
	 * @param AdminRequestQuery $query Administrator request query.
	 */
	public function __construct( private AdminRequestQuery $query ) {
	}

	/**
	 * Register menu and scoped assets.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Add request management beneath the booking menu.
	 */
	public function addMenu(): void {
		$this->hook_suffix = add_submenu_page(
			EmployeeDashboardPage::PAGE_SLUG,
			'مدیریت درخواست‌های رزرو',
			'مدیریت درخواست‌ها',
			RoleManager::MANAGE_BOOKINGS,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the protected administrator page.
	 */
	public function render(): void {
		if ( ! current_user_can( RoleManager::MANAGE_BOOKINGS ) ) {
			wp_die( esc_html__( 'شما اجازه مدیریت درخواست‌های رزرو را ندارید.', 'workshop-registration' ), 403 );
		}

		$status       = $this->selectedStatus();
		$date         = $this->selectedDate();
		$search       = $this->selectedSearch();
		$current_page = $this->selectedPage();
		$requests     = $this->query->findPage( $status, $date, $search, $current_page, self::REQUESTS_PER_PAGE );
		$notice       = $this->selectedNotice();

		include plugin_dir_path( WORKSHOP_REGISTRATION_FILE ) . 'templates/admin-requests.php';
	}

	/**
	 * Load styles and interactions only on this page.
	 *
	 * @param string $hook_suffix Current WordPress admin hook.
	 */
	public function enqueueAssets( string $hook_suffix ): void {
		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'workshop-admin-requests',
			plugins_url( 'assets/css/admin-requests.css', WORKSHOP_REGISTRATION_FILE ),
			array(),
			WORKSHOP_REGISTRATION_VERSION
		);
		wp_enqueue_script(
			'workshop-admin-requests',
			plugins_url( 'assets/js/admin-requests.js', WORKSHOP_REGISTRATION_FILE ),
			array(),
			WORKSHOP_REGISTRATION_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
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
	 * Map an allow-listed notice code to presentation data.
	 *
	 * @param string|null $notice Notice code.
	 * @return array{type:string,message:string}|null
	 */
	public function noticeData( ?string $notice ): ?array {
		return match ( $notice ) {
			'approved' => array(
				'type'    => 'success',
				'message' => 'درخواست با موفقیت تأیید شد.',
			),
			'rejected' => array(
				'type'    => 'success',
				'message' => 'درخواست رد شد و اتاق آن آزاد شد.',
			),
			'not_found' => array(
				'type'    => 'error',
				'message' => 'درخواست موردنظر پیدا نشد.',
			),
			'already_decided' => array(
				'type'    => 'error',
				'message' => 'این درخواست قبلاً بررسی شده و تصمیم آن نهایی است.',
			),
			'expired_form' => array(
				'type'    => 'error',
				'message' => 'اعتبار فرم پایان یافته است. دوباره تلاش کنید.',
			),
			'invalid_request' => array(
				'type'    => 'error',
				'message' => 'درخواست مدیریت معتبر نیست.',
			),
			'temporary_error' => array(
				'type'    => 'error',
				'message' => 'ثبت تصمیم انجام نشد. لطفاً دوباره تلاش کنید.',
			),
			default => null,
		};
	}

	/**
	 * Read an allow-listed status filter.
	 */
	private function selectedStatus(): ?WorkshopStatus {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET['request_status'] ) && is_string( $_GET['request_status'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_GET['request_status'] ) )
			: '';

		return WorkshopStatus::tryFrom( $value );
	}

	/**
	 * Read a strict optional meeting date filter.
	 */
	private function selectedDate(): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET['request_date'] ) && is_string( $_GET['request_date'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET['request_date'] ) )
			: '';

		if ( '' === $value ) {
			return null;
		}

		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );

		return false !== $date && $date->format( 'Y-m-d' ) === $value ? $value : null;
	}

	/**
	 * Read bounded administrator search text.
	 */
	private function selectedSearch(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET['request_search'] ) && is_string( $_GET['request_search'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET['request_search'] ) )
			: '';

		return mb_substr( $value, 0, 100 );
	}

	/**
	 * Read a bounded one-based page number.
	 */
	private function selectedPage(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return max( 1, min( 100000, absint( $_GET['request_page'] ?? 1 ) ) );
	}

	/**
	 * Read an allow-listed redirect notice.
	 */
	private function selectedNotice(): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET['decision_notice'] ) && is_string( $_GET['decision_notice'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_GET['decision_notice'] ) )
			: '';

		return null !== $this->noticeData( $value ) ? $value : null;
	}
}
