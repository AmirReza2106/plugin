<?php
/**
 * Plugin Name:       Workshop Registration
 * Plugin URI:        https://example.com/workshop-registration
 * Description:       Secure workshop registration and meeting room allocation.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Workshop Registration Team
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       workshop-registration
 * Domain Path:       /languages
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WORKSHOP_REGISTRATION_VERSION', '0.1.0' );
define( 'WORKSHOP_REGISTRATION_FILE', __FILE__ );
define( 'WORKSHOP_REGISTRATION_DIR', plugin_dir_path( __FILE__ ) );

$workshop_registration_autoloader = WORKSHOP_REGISTRATION_DIR . 'vendor/autoload.php';

if ( ! is_readable( $workshop_registration_autoloader ) ) {
	return;
}

require_once $workshop_registration_autoloader;

WorkshopRegistration\Plugin::boot();
