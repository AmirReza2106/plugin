<?php
/**
 * WordPress employee booking query tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Infrastructure\Database\WordPressEmployeeBookingQuery;
use wpdb;

/**
 * Verifies ownership predicates remain present on every personal-data query.
 */
final class WordPressEmployeeBookingQueryTest extends TestCase {
	/**
	 * List, total, and status-count queries are all scoped to the employee ID.
	 */
	public function test_every_query_is_scoped_to_the_authenticated_employee(): void {
		$database               = new wpdb();
		$database->result_queue = array(
			array(
				array(
					'id'               => '41',
					'public_reference' => 'request-reference',
					'workshop_title'   => 'Private planning',
					'workshop_date'    => '2030-05-20',
					'start_time'       => '09:00:00',
					'end_time'         => '09:30:00',
					'slot_number'      => '2',
					'status'           => 'pending',
					'created_at'       => '2030-05-01 08:00:00',
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

		$page = ( new WordPressEmployeeBookingQuery( $database, new Tables( 'wp_' ) ) )
			->findPage( 17, WorkshopStatus::Pending, 1, 10 );

		self::assertCount( 3, $database->prepared );
		foreach ( $database->prepared as $prepared ) {
			list( $sql, $parameters ) = $prepared;
			self::assertStringContainsString( 'requester_user_id = %d', $sql );
			self::assertContains( 17, $parameters );
		}
		self::assertSame( 'Private planning', $page->items[0]->workshopTitle );
		self::assertSame( 1, $page->totalItems );
		self::assertSame(
			array(
				'pending'  => 1,
				'approved' => 0,
				'rejected' => 0,
			),
			$page->statusCounts
		);
	}
}
