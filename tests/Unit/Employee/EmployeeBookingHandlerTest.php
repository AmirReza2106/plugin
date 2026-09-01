<?php
/**
 * Employee booking handler authorization tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Employee;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Access\RoleManager;
use WorkshopRegistration\Application\Registration\RegistrationServiceFactory;
use WorkshopRegistration\Employee\BookingInputValidator;
use WorkshopRegistration\Employee\EmployeeBookingHandler;
use WorkshopRegistration\Employee\EmployeeNoticeStore;
use WorkshopRegistration\Employee\EmployeeWordPressFunctionState;
use WorkshopRegistration\Employee\EmployeeWpDie;
use WorkshopRegistration\Infrastructure\Settings\SchedulingSettings;
use wpdb;

/**
 * Verifies unauthenticated and unauthorized requests stop before processing input.
 */
final class EmployeeBookingHandlerTest extends TestCase {
	/**
	 * Reset WordPress function doubles around every test.
	 */
	protected function setUp(): void {
		EmployeeWordPressFunctionState::reset();
	}

	/**
	 * Only the authenticated admin-post action is registered.
	 */
	public function test_it_registers_no_guest_submission_hook(): void {
		$this->handler()->register();

		self::assertArrayHasKey( 'admin_post_workshop_employee_booking', EmployeeWordPressFunctionState::$hooks );
		self::assertArrayNotHasKey( 'admin_post_nopriv_workshop_employee_booking', EmployeeWordPressFunctionState::$hooks );
	}

	/**
	 * Missing authentication or capability terminates with HTTP 403.
	 *
	 * @param bool  $logged_in   Authentication state.
	 * @param array $capabilities Granted capabilities.
	 * @phpstan-param list<string> $capabilities
	 */
	#[DataProvider( 'unauthorizedStateProvider' )]
	public function test_unauthorized_requests_are_rejected( bool $logged_in, array $capabilities ): void {
		EmployeeWordPressFunctionState::$logged_in    = $logged_in;
		EmployeeWordPressFunctionState::$capabilities = $capabilities;

		try {
			$this->handler()->handle();
			self::fail( 'Expected wp_die to terminate the unauthorized request.' );
		} catch ( EmployeeWpDie $exception ) {
			self::assertSame( 403, $exception->getCode() );
			self::assertSame( 'شما اجازه ثبت درخواست رزرو را ندارید.', $exception->getMessage() );
		}
	}

	/**
	 * Provide unauthorized authentication states.
	 *
	 * @return iterable<string, array{bool, list<string>}>
	 */
	public static function unauthorizedStateProvider(): iterable {
		yield 'guest with no capability' => array( false, array() );
		yield 'guest with forged capability' => array( false, array( RoleManager::CREATE_BOOKINGS ) );
		yield 'logged in without capability' => array( true, array() );
	}

	/**
	 * Build the handler with dependencies that remain unused on authorization failure.
	 */
	private function handler(): EmployeeBookingHandler {
		$settings = new SchedulingSettings();

		return new EmployeeBookingHandler(
			new RegistrationServiceFactory( new wpdb(), $settings ),
			$settings,
			new BookingInputValidator(),
			new EmployeeNoticeStore()
		);
	}
}
