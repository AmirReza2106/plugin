<?php
/**
 * Minimal wpdb test double.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! class_exists( 'wpdb' ) ) {
	// phpcs:disable PEAR.NamingConventions.ValidClassName.StartWithCapital
	/**
	 * Records prepared queries and returns queued database results.
	 */
	class wpdb {
		// phpcs:enable PEAR.NamingConventions.ValidClassName.StartWithCapital
		/**
		 * WordPress table prefix.
		 *
		 * @var string
		 */
		public string $prefix = 'wp_';

		/**
		 * Most recent database error.
		 *
		 * @var string
		 */
		public string $last_error = '';

		/**
		 * Recorded prepared queries.
		 *
		 * @var list<array{string, list<mixed>}>
		 */
		public array $prepared = array();

		/**
		 * Queued result sets.
		 *
		 * @var list<array<int, array<string, mixed>>|null>
		 */
		public array $result_queue = array();

		/**
		 * Queued scalar results.
		 *
		 * @var list<string|int|null>
		 */
		public array $var_queue = array();

		/**
		 * Queued single-row results.
		 *
		 * @var list<array<string, mixed>|null>
		 */
		public array $row_queue = array();

		/**
		 * Executed fixed statements.
		 *
		 * @var list<string>
		 */
		public array $queries = array();

		/**
		 * Recorded update calls.
		 *
		 * @var list<array{string, array<string, mixed>, array<string, mixed>}>
		 */
		public array $updates = array();

		/**
		 * Recorded insert calls.
		 *
		 * @var list<array{string, array<string, mixed>}>
		 */
		public array $inserts = array();

		/**
		 * Configured fixed-statement result.
		 *
		 * @var int|false
		 */
		public int|false $query_result = 1;

		/**
		 * Configured update result.
		 *
		 * @var int|false
		 */
		public int|false $update_result = 1;

		/**
		 * Configured insert result.
		 *
		 * @var int|false
		 */
		public int|false $insert_result = 1;

		/**
		 * Record one prepared query and its parameters.
		 *
		 * @param string $query Query template.
		 * @param mixed  ...$args Query arguments.
		 */
		public function prepare( string $query, mixed ...$args ): string {
			$this->prepared[] = array( $query, $args );
			return 'prepared-' . ( count( $this->prepared ) - 1 );
		}

		/**
		 * Return the next queued result set.
		 *
		 * @param string $query  Prepared query token.
		 * @param string $output Output format.
		 * @return array<int, array<string, mixed>>|null
		 */
		public function get_results( string $query, string $output ): ?array {
			unset( $query, $output );
			return array_shift( $this->result_queue );
		}

		/**
		 * Return the next queued scalar result.
		 *
		 * @param string $query Prepared query token.
		 * @return string|int|null
		 */
		public function get_var( string $query ): string|int|null {
			unset( $query );
			return array_shift( $this->var_queue );
		}

		/**
		 * Return the next queued single-row result.
		 *
		 * @param string $query  Prepared query token.
		 * @param string $output Output format.
		 * @return array<string, mixed>|null
		 */
		public function get_row( string $query, string $output ): ?array {
			unset( $query, $output );
			return array_shift( $this->row_queue );
		}

		/**
		 * Record a fixed statement.
		 *
		 * @param string $query Fixed statement.
		 * @return int|false
		 */
		public function query( string $query ): int|false {
			$this->queries[] = $query;
			return $this->query_result;
		}

		/**
		 * Record an update call.
		 *
		 * @param string               $table        Table name.
		 * @param array<string, mixed> $data         Updated values.
		 * @param array<string, mixed> $where        Update conditions.
		 * @param array<int, string>   $format       Value formats.
		 * @param array<int, string>   $where_format Condition formats.
		 * @return int|false
		 */
		public function update( string $table, array $data, array $where, array $format, array $where_format ): int|false {
			unset( $format, $where_format );
			$this->updates[] = array( $table, $data, $where );
			return $this->update_result;
		}

		/**
		 * Record an insert call.
		 *
		 * @param string               $table  Table name.
		 * @param array<string, mixed> $data   Inserted values.
		 * @param array<int, string>   $format Value formats.
		 * @return int|false
		 */
		public function insert( string $table, array $data, array $format ): int|false {
			unset( $format );
			$this->inserts[] = array( $table, $data );
			return $this->insert_result;
		}

		/**
		 * Escape SQL LIKE wildcard characters.
		 *
		 * @param string $text Search text.
		 */
		public function esc_like( string $text ): string {
			return addcslashes( $text, '_%\\' );
		}
	}
}
