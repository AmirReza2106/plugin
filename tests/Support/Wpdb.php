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
	}
}
