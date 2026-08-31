<?php
/**
 * Plugin database table names.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

/**
 * Provides prefixed custom table names from one source.
 */
final class Tables {
	/**
	 * WordPress database table prefix.
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Create the plugin table-name provider.
	 *
	 * @param string $prefix WordPress database table prefix.
	 */
	public function __construct( string $prefix ) {
		$this->prefix = $prefix;
	}

	/**
	 * Get the workshop requests table name.
	 */
	public function requests(): string {
		return $this->prefix . 'workshop_requests';
	}

	/**
	 * Get the workshop status history table name.
	 */
	public function statusHistory(): string {
		return $this->prefix . 'workshop_status_history';
	}
}
