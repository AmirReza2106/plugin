<?php
/**
 * WordPress function doubles for administrator unit tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Admin;

use WorkshopRegistration\Employee\EmployeeWordPressFunctionState;
use WorkshopRegistration\Employee\EmployeeWpDie;

/**
 * Record an administrator WordPress action registration.
 *
 * @param string   $hook     Hook name.
 * @param callable $callback Hook callback.
 */
function add_action( string $hook, callable $callback ): void {
	EmployeeWordPressFunctionState::$hooks[ $hook ] = $callback;
}

/**
 * Return the configured authentication state.
 */
function is_user_logged_in(): bool {
	return EmployeeWordPressFunctionState::$logged_in;
}

/**
 * Check a capability against configured test state.
 *
 * @param string $capability Capability name.
 */
function current_user_can( string $capability ): bool {
	return in_array( $capability, EmployeeWordPressFunctionState::$capabilities, true );
}

/**
 * Return translated text unchanged in isolated tests.
 *
 * @param string $text   Source text.
 * @param string $domain Text domain.
 */
function esc_html__( string $text, string $domain ): string {
	unset( $domain );
	return $text;
}

/**
 * Terminate a request as WordPress would.
 *
 * @param string $message Error message.
 * @param int    $status  HTTP status.
 * @throws EmployeeWpDie Always.
 */
function wp_die( string $message, int $status ): never {
	// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
	throw new EmployeeWpDie( $message, $status );
}
