<?php
/**
 * Employee one-time notice store tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Employee;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Application\Registration\RegistrationResult;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Employee\EmployeeNoticeStore;
use WorkshopRegistration\Employee\EmployeeWordPressFunctionState;

/**
 * Verifies notice ownership, opacity, and one-time consumption.
 */
final class EmployeeNoticeStoreTest extends TestCase {
	/**
	 * Reset transient doubles around every test.
	 */
	protected function setUp(): void {
		EmployeeWordPressFunctionState::reset();
	}

	/**
	 * A success receipt exposes no data and can be consumed once by its owner.
	 */
	public function test_success_notice_is_private_and_one_time(): void {
		$store   = new EmployeeNoticeStore();
		$receipt = $store->createSuccess(
			17,
			new RegistrationResult( 41, 'request-reference', 2, WorkshopStatus::Pending )
		);

		self::assertMatchesRegularExpression( '/\A[a-f0-9]{64}\z/', $receipt );
		self::assertStringNotContainsString( 'request-reference', $receipt );
		self::assertNull( $store->consume( 18, $receipt ) );
		self::assertNull( $store->consume( 17, $receipt ) );
	}

	/**
	 * The owning employee receives only the allow-listed success payload.
	 */
	public function test_owner_can_consume_success_payload(): void {
		$store   = new EmployeeNoticeStore();
		$receipt = $store->createSuccess(
			17,
			new RegistrationResult( 41, 'request-reference', 2, WorkshopStatus::Pending )
		);

		self::assertSame(
			array(
				'type'      => 'success',
				'reference' => 'request-reference',
				'room'      => 2,
			),
			$store->consume( 17, $receipt )
		);
		self::assertNull( $store->consume( 17, $receipt ) );
	}

	/**
	 * Invalid receipts never reach transient storage.
	 */
	public function test_invalid_receipt_is_rejected(): void {
		self::assertNull( ( new EmployeeNoticeStore() )->consume( 17, '../invalid' ) );
		self::assertSame( array(), EmployeeWordPressFunctionState::$transients );
	}
}
