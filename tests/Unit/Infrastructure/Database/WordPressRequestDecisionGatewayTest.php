<?php
/**
 * Transactional request decision gateway tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Application\Exception\InvalidStatusTransition;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Domain\WorkshopStatus;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Infrastructure\Database\WordPressRequestDecisionGateway;
use wpdb;

/**
 * Verifies row locking, room release, history, and rollback behavior.
 */
final class WordPressRequestDecisionGatewayTest extends TestCase {
	/**
	 * Rejection clears the room while preserving its previous value in history.
	 */
	public function test_rejection_releases_room_and_records_history_atomically(): void {
		$database            = new wpdb();
		$database->row_queue = array(
			array(
				'status'      => 'pending',
				'slot_number' => '2',
			),
		);

		( new WordPressRequestDecisionGateway( $database, new Tables( 'wp_' ) ) )
			->decide( 41, WorkshopStatus::Rejected, 7, '2030-05-01 08:30:00' );

		self::assertSame( array( 'START TRANSACTION', 'COMMIT' ), $database->queries );
		self::assertNull( $database->updates[0][1]['slot_number'] );
		self::assertSame(
			array(
				'id'     => 41,
				'status' => 'pending',
			),
			$database->updates[0][2]
		);
		self::assertSame( 2, $database->inserts[0][1]['previous_slot_number'] );
		self::assertNull( $database->inserts[0][1]['new_slot_number'] );
		self::assertSame( 7, $database->inserts[0][1]['actor_user_id'] );
	}

	/**
	 * Approval retains the assigned room in both request and history.
	 */
	public function test_approval_retains_room_assignment(): void {
		$database            = new wpdb();
		$database->row_queue = array(
			array(
				'status'      => 'pending',
				'slot_number' => '3',
			),
		);

		( new WordPressRequestDecisionGateway( $database, new Tables( 'wp_' ) ) )
			->decide( 41, WorkshopStatus::Approved, 7, '2030-05-01 08:30:00' );

		self::assertSame( 3, $database->updates[0][1]['slot_number'] );
		self::assertSame( 3, $database->inserts[0][1]['new_slot_number'] );
	}

	/**
	 * A finalized request cannot receive a second decision.
	 */
	public function test_final_request_is_not_changed_again(): void {
		$database            = new wpdb();
		$database->row_queue = array(
			array(
				'status'      => 'approved',
				'slot_number' => '2',
			),
		);

		$this->expectException( InvalidStatusTransition::class );

		try {
			( new WordPressRequestDecisionGateway( $database, new Tables( 'wp_' ) ) )
				->decide( 41, WorkshopStatus::Rejected, 7, '2030-05-01 08:30:00' );
		} finally {
			self::assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $database->queries );
			self::assertSame( array(), $database->updates );
		}
	}

	/**
	 * History failure rolls the request update back.
	 */
	public function test_history_failure_rolls_back_the_transaction(): void {
		$database                = new wpdb();
		$database->row_queue     = array(
			array(
				'status'      => 'pending',
				'slot_number' => '2',
			),
		);
		$database->insert_result = false;

		$this->expectException( PersistenceFailure::class );

		try {
			( new WordPressRequestDecisionGateway( $database, new Tables( 'wp_' ) ) )
				->decide( 41, WorkshopStatus::Approved, 7, '2030-05-01 08:30:00' );
		} finally {
			self::assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $database->queries );
		}
	}
}
