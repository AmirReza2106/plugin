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
	public const SCHEMA_VERSION = '2.0.0';

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

		$installed_version = get_option( self::VERSION_OPTION, false );

		if ( is_string( $installed_version ) && version_compare( $installed_version, '2.0.0', '<' ) ) {
			$this->migrateEmployeeOwnership();
		}

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

	/**
	 * Replace ownerless public requests with mandatory employee ownership.
	 *
	 * The product decision explicitly requires deleting all legacy requests.
	 *
	 * @throws RuntimeException When a migration statement fails.
	 */
	private function migrateEmployeeOwnership(): void {
		$requests = $this->tables->requests();
		$history  = $this->tables->statusHistory();

		if ( ! $this->columnExists( $requests, 'requester_user_id' ) ) {
			$this->executeMigrationStatement( "ALTER TABLE {$requests} ADD requester_user_id bigint(20) unsigned DEFAULT NULL AFTER id" );
		}

		$this->executeMigrationStatement(
			"DELETE FROM {$history}
			WHERE request_id IN (
				SELECT id FROM {$requests}
				WHERE requester_user_id IS NULL OR requester_user_id = 0
			)"
		);
		$this->executeMigrationStatement(
			"DELETE FROM {$requests} WHERE requester_user_id IS NULL OR requester_user_id = 0"
		);

		if ( $this->columnExists( $requests, 'tracking_token_hash' ) ) {
			$this->executeMigrationStatement( "ALTER TABLE {$requests} DROP COLUMN tracking_token_hash" );
		}

		$this->executeMigrationStatement(
			"ALTER TABLE {$requests} MODIFY requester_user_id bigint(20) unsigned NOT NULL"
		);
	}

	/**
	 * Determine whether a trusted custom table contains a column.
	 *
	 * @param string $table  Trusted custom table name.
	 * @param string $column Expected column name.
	 */
	private function columnExists( string $table, string $column ): bool {
		$query = $this->database->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table, $column );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return null !== $this->database->get_var( $query );
	}

	/**
	 * Execute a trusted schema migration statement.
	 *
	 * @param string $statement Migration DDL or data cleanup statement.
	 * @throws RuntimeException When MariaDB rejects the statement.
	 */
	private function executeMigrationStatement( string $statement ): void {
		// Statements are assembled only from trusted plugin table names and constants.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $this->database->query( $statement ) ) {
			// The exception is logged privately and never rendered to public users.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( $this->database->last_error );
		}
	}
}
