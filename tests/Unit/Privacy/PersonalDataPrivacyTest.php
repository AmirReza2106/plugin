<?php
/**
 * Personal-data privacy integration tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Privacy;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Privacy\PersonalDataPrivacy;
use wpdb;

/**
 * Verifies export shape and non-destructive booking anonymization.
 */
final class PersonalDataPrivacyTest extends TestCase {
	/**
	 * Matching submitted data is exported through WordPress privacy format.
	 */
	public function test_it_exports_booking_personal_data(): void {
		$database               = new wpdb();
		$database->result_queue = array(
			array(
				array(
					'id'               => '41',
					'public_reference' => 'request-reference',
					'first_name'       => 'Jane',
					'last_name'        => 'Doe',
					'mobile'           => '+15550100',
					'email'            => 'jane@example.test',
					'workshop_title'   => 'Planning',
					'workshop_date'    => '2030-05-20',
					'start_time'       => '09:00:00',
					'end_time'         => '09:30:00',
					'description'      => 'Private details',
					'status'           => 'pending',
					'slot_number'      => '2',
					'created_at'       => '2030-05-01 08:00:00',
				),
			),
		);

		$result = ( new PersonalDataPrivacy( $database, new Tables( 'wp_' ) ) )
			->export( 'jane@example.test' );

		self::assertTrue( $result['done'] );
		self::assertSame( 'workshop-request-41', $result['data'][0]['item_id'] );
		self::assertSame( 'Jane', $result['data'][0]['data'][1]['value'] );
		self::assertContains( 'jane@example.test', $database->prepared[0][1] );
	}

	/**
	 * Erasure anonymizes identity while retaining operational request rows.
	 */
	public function test_it_anonymizes_instead_of_deleting_schedules(): void {
		$database = new wpdb();

		$result = ( new PersonalDataPrivacy( $database, new Tables( 'wp_' ) ) )
			->erase( 'jane@example.test' );

		self::assertTrue( $result['items_removed'] );
		self::assertFalse( $result['items_retained'] );
		self::assertCount( 1, $database->queries );
		self::assertStringContainsString( 'UPDATE %i SET requester_user_id = %d', $database->prepared[0][0] );
		self::assertContains( 'جلسه حذف‌شده', $database->prepared[0][1] );
		self::assertStringNotContainsString( 'DELETE', $database->prepared[0][0] );
	}
}
