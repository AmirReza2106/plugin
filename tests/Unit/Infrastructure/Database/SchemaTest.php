<?php
/**
 * Database schema tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Infrastructure\Database\Schema;
use WorkshopRegistration\Infrastructure\Database\Tables;

/**
 * Verifies the deterministic custom table definitions.
 */
final class SchemaTest extends TestCase {
	/**
	 * The schema defines requests and immutable status history tables.
	 */
	public function test_it_builds_both_custom_table_statements(): void {
		$statements = ( new Schema() )->statements(
			new Tables( 'wp_' ),
			'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
		);

		self::assertCount( 2, $statements );
		self::assertStringContainsString( 'CREATE TABLE wp_workshop_requests', $statements[0] );
		self::assertStringContainsString( 'requester_user_id bigint(20) unsigned NOT NULL', $statements[0] );
		self::assertStringContainsString( 'KEY requester_status_date', $statements[0] );
		self::assertStringNotContainsString( 'tracking_token_hash', $statements[0] );
		self::assertStringContainsString( 'KEY allocation_lookup', $statements[0] );
		self::assertStringContainsString( 'CREATE TABLE wp_workshop_status_history', $statements[1] );
		self::assertStringContainsString( 'KEY request_history', $statements[1] );
	}
}
