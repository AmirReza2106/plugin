<?php
/**
 * WordPress administrator request query tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Infrastructure\Database\WordPressAdminRequestQuery;
use wpdb;

/**
 * Verifies bounded administrator filters and complete request projections.
 */
final class WordPressAdminRequestQueryTest extends TestCase {
	/**
	 * Status, date, and escaped search filters apply to list and total queries.
	 */
	public function test_it_applies_filters_and_maps_complete_request_details(): void {
		$database               = new wpdb();
		$database->result_queue = array(
			array(
				array(
					'id'                => '41',
					'requester_user_id' => '17',
					'public_reference'  => 'request-reference',
					'first_name'        => 'Jane',
					'last_name'         => 'Doe',
					'mobile'            => '+15550100',
					'email'             => 'jane@example.test',
					'workshop_title'    => 'Private planning',
					'workshop_date'     => '2030-05-20',
					'start_time'        => '09:00:00',
					'end_time'          => '09:30:00',
					'description'       => 'Internal details',
					'status'            => 'pending',
					'slot_number'       => '2',
					'reviewed_by'       => null,
					'status_changed_at' => null,
					'created_at'        => '2030-05-01 08:00:00',
				),
			),
			array(
				array(
					'status' => 'pending',
					'total'  => '1',
				),
			),
		);
		$database->var_queue    = array( '1' );

		$page = ( new WordPressAdminRequestQuery( $database, new Tables( 'wp_' ) ) )
			->findPage( WorkshopStatus::Pending, '2030-05-20', 'plan_%', 1, 20 );

		self::assertCount( 3, $database->prepared );
		self::assertStringContainsString( 'status = %s', $database->prepared[0][0] );
		self::assertStringContainsString( 'workshop_date = %s', $database->prepared[0][0] );
		self::assertStringContainsString( 'public_reference LIKE %s', $database->prepared[0][0] );
		self::assertContains( '%plan\_\%%', $database->prepared[0][1] );
		self::assertStringContainsString( 'status = %s', $database->prepared[1][0] );
		self::assertSame( 'Private planning', $page->items[0]->workshopTitle );
		self::assertSame( '+15550100', $page->items[0]->mobile );
		self::assertSame( 1, $page->statusCounts['pending'] );
	}
}
