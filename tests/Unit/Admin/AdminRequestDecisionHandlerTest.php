<?php
/**
 * Administrator request decision handler tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Admin;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Access\RoleManager;
use WorkshopRegistration\Admin\AdminRequestDecisionHandler;
use WorkshopRegistration\Application\AdminRequests\DecideRequest;
use WorkshopRegistration\Application\Contracts\Clock;
use WorkshopRegistration\Application\Contracts\RequestDecisionGateway;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Employee\EmployeeWordPressFunctionState;
use WorkshopRegistration\Employee\EmployeeWpDie;

/**
 * Verifies the administrator endpoint remains authenticated and capability-gated.
 */
final class AdminRequestDecisionHandlerTest extends TestCase {
	/**
	 * Reset WordPress function doubles around every test.
	 */
	protected function setUp(): void {
		EmployeeWordPressFunctionState::reset();
	}

	/**
	 * Only the authenticated admin-post decision action is registered.
	 */
	public function test_it_registers_no_guest_decision_hook(): void {
		$this->handler()->register();

		self::assertArrayHasKey( 'admin_post_workshop_admin_request_decision', EmployeeWordPressFunctionState::$hooks );
		self::assertArrayNotHasKey( 'admin_post_nopriv_workshop_admin_request_decision', EmployeeWordPressFunctionState::$hooks );
	}

	/**
	 * Missing authentication or management capability terminates with HTTP 403.
	 *
	 * @param bool  $logged_in   Authentication state.
	 * @param array $capabilities Granted capabilities.
	 * @phpstan-param list<string> $capabilities
	 */
	#[DataProvider( 'unauthorizedStateProvider' )]
	public function test_unauthorized_decisions_are_rejected( bool $logged_in, array $capabilities ): void {
		EmployeeWordPressFunctionState::$logged_in    = $logged_in;
		EmployeeWordPressFunctionState::$capabilities = $capabilities;

		try {
			$this->handler()->handle();
			self::fail( 'Expected wp_die to terminate the unauthorized decision.' );
		} catch ( EmployeeWpDie $exception ) {
			self::assertSame( 403, $exception->getCode() );
			self::assertSame( 'شما اجازه مدیریت درخواست‌های رزرو را ندارید.', $exception->getMessage() );
		}
	}

	/**
	 * Provide unauthorized authentication states.
	 *
	 * @return iterable<string, array{bool, list<string>}>
	 */
	public static function unauthorizedStateProvider(): iterable {
		yield 'guest' => array( false, array() );
		yield 'guest with forged capability' => array( false, array( RoleManager::MANAGE_BOOKINGS ) );
		yield 'employee without management capability' => array( true, array( RoleManager::CREATE_BOOKINGS ) );
	}

	/**
	 * Build a handler whose use-case collaborators are unused on authorization failure.
	 */
	private function handler(): AdminRequestDecisionHandler {
		$gateway = new class() implements RequestDecisionGateway {
			/**
			 * Ignore an unreachable test decision.
			 *
			 * @param int            $request_id   Request ID.
			 * @param WorkshopStatus $target        Target status.
			 * @param int            $actor_user_id Actor ID.
			 * @param string         $changed_at    UTC timestamp.
			 */
			public function decide( int $request_id, WorkshopStatus $target, int $actor_user_id, string $changed_at ): void {
				unset( $request_id, $target, $actor_user_id, $changed_at );
			}
		};
		$clock   = new class() implements Clock {
			/**
			 * Return a deterministic test time.
			 *
			 * @param DateTimeZone $timezone Requested timezone.
			 */
			public function now( DateTimeZone $timezone ): DateTimeImmutable {
				return new DateTimeImmutable( '2030-05-01 08:30:00', $timezone );
			}
		};

		return new AdminRequestDecisionHandler( new DecideRequest( $gateway, $clock ) );
	}
}
