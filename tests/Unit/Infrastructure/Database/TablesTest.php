<?php
/**
 * Database table name tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Infrastructure\Database\Tables;

/**
 * Verifies that custom tables respect the WordPress prefix.
 */
final class TablesTest extends TestCase {
	/**
	 * All plugin table names use the supplied prefix.
	 */
	public function test_it_builds_prefixed_table_names(): void {
		$tables = new Tables( 'company_' );

		self::assertSame( 'company_workshop_requests', $tables->requests() );
		self::assertSame( 'company_workshop_status_history', $tables->statusHistory() );
	}
}
