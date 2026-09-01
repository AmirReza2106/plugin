<?php
/**
 * Employee WordPress function-double state.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Employee;

use RuntimeException;

/**
 * Represents a terminating wp_die call in an isolated unit test.
 */
final class EmployeeWpDie extends RuntimeException {
}

/**
 * Holds deterministic transient state for employee unit tests.
 */
final class EmployeeWordPressFunctionState {
	/**
	 * Stored test transients.
	 *
	 * @var array<string, mixed>
	 */
	public static array $transients = array();

	/**
	 * Registered test hooks.
	 *
	 * @var array<string, callable>
	 */
	public static array $hooks = array();

	/**
	 * Configured login state.
	 *
	 * @var bool
	 */
	public static bool $logged_in = false;

	/**
	 * Granted test capabilities.
	 *
	 * @var list<string>
	 */
	public static array $capabilities = array();

	/**
	 * Reset all function-double state.
	 */
	public static function reset(): void {
		self::$transients   = array();
		self::$hooks        = array();
		self::$logged_in    = false;
		self::$capabilities = array();
	}
}
