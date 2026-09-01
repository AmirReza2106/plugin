<?php
/**
 * WordPress function doubles for privacy unit tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Privacy;

/**
 * Simulate no matching current WordPress account.
 *
 * @param string $field User lookup field.
 * @param string $value User lookup value.
 */
function get_user_by( string $field, string $value ): false {
	unset( $field, $value );
	return false;
}
