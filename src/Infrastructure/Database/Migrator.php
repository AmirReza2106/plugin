<?php
/**
 * Plugin database migration runner.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

use RuntimeException;
use wpdb;

/**
 * Applies idempotent custom table migrations and tracks their version.
 */
final class Migrator {
	public const SCHEMA_VERSION = '1.0.0';

	public const VERSION_OPTION = 'workshop_registration_schema_version';

	/**
	 * WordPress database connection.
	 *
	 * @var wpdb
	 */
	private wpdb $database;

	/**
	 * Plugin schema definition.
	 *
	 * @var Schema
	 */
	private Schema $schema;

	/**
	 * Plugin table names.
	 *
	 * @var Tables
	 */
	private Tables $tables;

	/**
	 * Create the database migrator.
	 *
	 * @param wpdb   $database WordPress database connection.
	 * @param Schema $schema   Plugin schema definition.
	 * @param Tables $tables   Plugin table names.
	 */
	public function __construct( wpdb $database, Schema $schema, Tables $tables ) {
		$this->database = $database;
		$this->schema   = $schema;
		$this->tables   = $tables;
	}

	/**
	 * Determine whether the installed schema is current.
	 */
	public function isCurrent(): bool {
		return self::SCHEMA_VERSION === get_option( self::VERSION_OPTION );
	}

	/**
	 * Create or update the plugin tables.
	 *
	 * @throws RuntimeException When WordPress reports a database error.
	 */
	public function migrate(): void {
		if ( $this->isCurrent() ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $this->schema->statements( $this->tables, $this->database->get_charset_collate() ) as $statement ) {
			dbDelta( $statement );

			$database_error = $this->database->last_error;

			if ( '' !== $database_error ) {
				// The exception is logged, while the admin notice remains generic.
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( $database_error );
			}
		}

		if ( false === get_option( self::VERSION_OPTION, false ) ) {
			add_option( self::VERSION_OPTION, self::SCHEMA_VERSION, '', false );
			return;
		}

		update_option( self::VERSION_OPTION, self::SCHEMA_VERSION, false );
	}
}
