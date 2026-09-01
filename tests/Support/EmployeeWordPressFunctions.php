<?php
/**
 * WordPress function doubles for employee unit tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Employee;

/**
 * Approximate WordPress single-line text sanitization for isolated unit tests.
 *
 * @param string $value Raw value.
 */
function sanitize_text_field( string $value ): string {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	return trim( strip_tags( preg_replace( '/[\r\n\t ]+/', ' ', $value ) ?? '' ) );
}

/**
 * Approximate WordPress textarea sanitization for isolated unit tests.
 *
 * @param string $value Raw value.
 */
function sanitize_textarea_field( string $value ): string {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	return trim( strip_tags( $value ) );
}

/**
 * Validate an email using the same false-or-string contract as WordPress.
 *
 * @param string $email Candidate email.
 * @return string|false
 */
function is_email( string $email ): string|false {
	return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
}

/**
 * Store a test transient.
 *
 * @param string $key        Transient key.
 * @param mixed  $value      Stored value.
 * @param int    $expiration Ignored expiration.
 */
function set_transient( string $key, mixed $value, int $expiration ): bool {
	unset( $expiration );
	EmployeeWordPressFunctionState::$transients[ $key ] = $value;
	return true;
}

/**
 * Read a test transient.
 *
 * @param string $key Transient key.
 * @return mixed
 */
function get_transient( string $key ): mixed {
	return EmployeeWordPressFunctionState::$transients[ $key ] ?? false;
}

/**
 * Delete a test transient.
 *
 * @param string $key Transient key.
 */
function delete_transient( string $key ): bool {
	$exists = array_key_exists( $key, EmployeeWordPressFunctionState::$transients );
	unset( EmployeeWordPressFunctionState::$transients[ $key ] );
	return $exists;
}

/**
 * Record a WordPress action registration.
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
