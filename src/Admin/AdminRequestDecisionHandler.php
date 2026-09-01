<?php
/**
 * Administrator request decision handler.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Admin;

use Throwable;
use WorkshopRegistration\Access\RoleManager;
use WorkshopRegistration\Application\AdminRequests\DecideRequest;
use WorkshopRegistration\Application\Exception\InvalidStatusTransition;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Application\Exception\RequestNotFound;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Converts an authorized administrator POST into one final decision.
 */
final class AdminRequestDecisionHandler {
	public const ACTION = 'workshop_admin_request_decision';

	public const NONCE_NAME = 'workshop_admin_decision_nonce';

	private const MAX_REQUEST_BYTES = 8192;

	/**
	 * Create the decision handler.
	 *
	 * @param DecideRequest $service Decision use case.
	 */
	public function __construct( private DecideRequest $service ) {
	}

	/**
	 * Register the authenticated WordPress action only.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Process a final request decision and redirect.
	 */
	public function handle(): void {
		if ( ! is_user_logged_in() || ! current_user_can( RoleManager::MANAGE_BOOKINGS ) ) {
			wp_die( esc_html__( 'شما اجازه مدیریت درخواست‌های رزرو را ندارید.', 'workshop-registration' ), 403 );
		}

		if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
			$this->redirect( 'invalid_request' );
		}

		$content_length = sanitize_text_field( wp_unslash( $_SERVER['CONTENT_LENGTH'] ?? '0' ) );
		if ( (int) $content_length > self::MAX_REQUEST_BYTES ) {
			$this->redirect( 'invalid_request' );
		}

		$input = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce = isset( $input[ self::NONCE_NAME ] ) && is_string( $input[ self::NONCE_NAME ] )
			? $input[ self::NONCE_NAME ]
			: '';

		if ( 1 !== wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->redirect( 'expired_form' );
		}

		$request_id = isset( $input['request_id'] ) ? absint( $input['request_id'] ) : 0;
		$decision   = isset( $input['decision'] ) && is_string( $input['decision'] )
			? sanitize_key( $input['decision'] )
			: '';
		$target     = match ( $decision ) {
			'approve' => WorkshopStatus::Approved,
			'reject' => WorkshopStatus::Rejected,
			default => null,
		};

		if ( $request_id < 1 || null === $target ) {
			$this->redirect( 'invalid_request' );
		}

		try {
			$this->service->execute( $request_id, $target, get_current_user_id() );
			$this->redirect( $target->value );
		} catch ( RequestNotFound $exception ) {
			$this->redirect( 'not_found' );
		} catch ( InvalidStatusTransition $exception ) {
			$this->redirect( 'already_decided' );
		} catch ( PersistenceFailure $exception ) {
			$this->redirect( 'temporary_error' );
		} catch ( Throwable $exception ) {
			// Never log submitted request data.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Administrator workshop decision failed with an unexpected internal error.' );
			$this->redirect( 'temporary_error' );
		}
	}

	/**
	 * Redirect to the fixed administrator request page.
	 *
	 * @param string $notice Safe notice code.
	 */
	private function redirect( string $notice ): never {
		$url = add_query_arg(
			array(
				'page'            => AdminRequestsPage::PAGE_SLUG,
				'decision_notice' => $notice,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url, 303, 'Workshop Registration' );
		exit;
	}
}
