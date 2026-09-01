<?php
/**
 * Authenticated employee booking handler.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Employee;

use Throwable;
use WorkshopRegistration\Access\RoleManager;
use WorkshopRegistration\Application\Exception\BookingLockTimeout;
use WorkshopRegistration\Application\Exception\InvalidWorkshopDate;
use WorkshopRegistration\Application\Exception\NoRoomAvailable;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Application\Registration\RegistrationServiceFactory;
use WorkshopRegistration\Domain\Scheduling\InvalidBookingTime;
use WorkshopRegistration\Infrastructure\Settings\SchedulingSettings;

/**
 * Converts an authorized employee POST into one atomic booking request.
 */
final class EmployeeBookingHandler {
	public const ACTION = 'workshop_employee_booking';

	public const NONCE_NAME = 'workshop_employee_booking_nonce';

	private const MAX_REQUEST_BYTES = 32768;

	/**
	 * Create the authenticated booking handler.
	 *
	 * @param RegistrationServiceFactory $service_factory Registration composition factory.
	 * @param SchedulingSettings         $settings        Scheduling settings source.
	 * @param BookingInputValidator      $validator       Employee input validator.
	 * @param EmployeeNoticeStore        $notice_store    One-time notice store.
	 */
	public function __construct(
		private RegistrationServiceFactory $service_factory,
		private SchedulingSettings $settings,
		private BookingInputValidator $validator,
		private EmployeeNoticeStore $notice_store
	) {
	}

	/**
	 * Register the authenticated WordPress action only.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Process a booking and redirect to the employee dashboard.
	 */
	public function handle(): void {
		if ( ! is_user_logged_in() || ! current_user_can( RoleManager::CREATE_BOOKINGS ) ) {
			wp_die( esc_html__( 'شما اجازه ثبت درخواست رزرو را ندارید.', 'workshop-registration' ), 403 );
		}

		$employee_user_id = get_current_user_id();

		if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
			$this->redirectError( $employee_user_id, 'invalid_request' );
		}

		$content_length = sanitize_text_field( wp_unslash( $_SERVER['CONTENT_LENGTH'] ?? '0' ) );
		if ( (int) $content_length > self::MAX_REQUEST_BYTES ) {
			$this->redirectError( $employee_user_id, 'request_too_large' );
		}

		$input = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce = isset( $input[ self::NONCE_NAME ] ) && is_string( $input[ self::NONCE_NAME ] )
			? $input[ self::NONCE_NAME ]
			: '';

		if ( 1 !== wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->redirectError( $employee_user_id, 'expired_form' );
		}

		try {
			$registration = $this->validator->validate( $input, wp_timezone_string() );
			$result       = $this->service_factory->create()->register(
				$registration,
				$employee_user_id,
				$this->settings->roomCapacity()
			);
			$receipt      = $this->notice_store->createSuccess( $employee_user_id, $result );

			$this->redirect( $receipt );
		} catch ( BookingValidationFailure $exception ) {
			$this->redirectError( $employee_user_id, $exception->getMessage() );
		} catch ( InvalidBookingTime | InvalidWorkshopDate $exception ) {
			$this->redirectError( $employee_user_id, 'invalid_schedule' );
		} catch ( NoRoomAvailable $exception ) {
			$this->redirectError( $employee_user_id, 'no_room' );
		} catch ( BookingLockTimeout $exception ) {
			$this->redirectError( $employee_user_id, 'booking_busy' );
		} catch ( PersistenceFailure $exception ) {
			$this->redirectError( $employee_user_id, 'temporary_error' );
		} catch ( Throwable $exception ) {
			// Never log submitted personal details or raw request data.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Employee workshop booking failed with an unexpected internal error.' );
			$this->redirectError( $employee_user_id, 'temporary_error' );
		}
	}

	/**
	 * Store a safe error notice and redirect.
	 *
	 * @param int    $employee_user_id Owning employee user ID.
	 * @param string $code             Candidate error code.
	 */
	private function redirectError( int $employee_user_id, string $code ): never {
		$allowed   = array(
			'invalid_request',
			'request_too_large',
			'expired_form',
			'missing_fields',
			'input_too_long',
			'invalid_input',
			'invalid_email',
			'invalid_mobile',
			'invalid_schedule',
			'no_room',
			'booking_busy',
			'temporary_error',
		);
		$safe_code = in_array( $code, $allowed, true ) ? $code : 'invalid_input';
		$receipt   = $this->notice_store->createError( $employee_user_id, $safe_code );

		$this->redirect( $receipt );
	}

	/**
	 * Redirect to the fixed employee dashboard URL.
	 *
	 * @param string $receipt Random notice receipt.
	 */
	private function redirect( string $receipt ): never {
		$url = add_query_arg(
			array(
				'page'           => EmployeeDashboardPage::PAGE_SLUG,
				'booking_notice' => rawurlencode( $receipt ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url, 303, 'Workshop Registration' );
		exit;
	}
}
