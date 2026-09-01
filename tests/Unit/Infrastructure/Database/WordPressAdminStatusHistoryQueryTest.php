<?php
/**
 * WordPress administrator status history query tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Infrastructure\Database\WordPressAdminStatusHistoryQuery;
use wpdb;

/**
 * Verifies bounded batch loading and immutable audit projections.
 */
final class WordPressAdminStatusHistoryQueryTest extends TestCase {
	/**
	 * Events are grouped by visible request and reviewer names are mapped.
	 */
	public function test_it_loads_and_groups_history_in_one_query(): void {
		$database               = new wpdb();
		$database->result_queue = array(
			array(
				array(
					'request_id'           => '41',
					'from_status'          => null,
					'to_status'            => 'pending',
					'previous_slot_number' => null,
					'new_slot_number'      => '2',
					'actor_user_id'        => null,
					'actor_display_name'   => null,
					'created_at'           => '2030-05-01 08:00:00',
				),
				array(
					'request_id'           => '41',
					'from_status'          => 'pending',
					'to_status'            => 'approved',
					'previous_slot_number' => '2',
					'new_slot_number'      => '2',
					'actor_user_id'        => '7',
					'actor_display_name'   => 'Administrator',
					'created_at'           => '2030-05-01 09:00:00',
				),
			),
		);

		$history = ( new WordPressAdminStatusHistoryQuery( $database, new Tables( 'wp_' ) ) )
			->findByRequestIds( array( 41, 42 ) );

		self::assertCount( 1, $database->prepared );
		self::assertStringContainsString( 'history.request_id IN (%d, %d)', $database->prepared[0][0] );
		self::assertSame( array(), $history[42] );
		self::assertCount( 2, $history[41] );
		self::assertSame( WorkshopStatus::Pending, $history[41][0]->toStatus );
		self::assertSame( WorkshopStatus::Approved, $history[41][1]->toStatus );
		self::assertSame( 'Administrator', $history[41][1]->actorDisplayName );
	}

	/**
	 * Empty pages do not execute a database query.
	 */
	public function test_empty_request_page_skips_database(): void {
		$database = new wpdb();

		self::assertSame(
			array(),
			( new WordPressAdminStatusHistoryQuery( $database, new Tables( 'wp_' ) ) )->findByRequestIds( array() )
		);
		self::assertSame( array(), $database->prepared );
	}
}
