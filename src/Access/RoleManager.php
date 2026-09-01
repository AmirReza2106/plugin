<?php
/**
 * Employee role and booking capabilities.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Access;

/**
 * Provisions least-privilege employee and administrator capabilities.
 */
final class RoleManager {
	public const ROLE = 'employee';

	public const CREATE_BOOKINGS = 'create_workshop_bookings';

	public const VIEW_OWN_BOOKINGS = 'view_own_workshop_bookings';

	public const VIEW_AVAILABILITY = 'view_room_availability';

	public const MANAGE_BOOKINGS = 'manage_workshop_requests';

	private const ACCESS_VERSION = '1.0.0';

	private const VERSION_OPTION = 'workshop_registration_access_version';

	/**
	 * Provision capabilities only when their version changes.
	 */
	public function maybeInstall(): void {
		if ( self::ACCESS_VERSION === get_option( self::VERSION_OPTION ) ) {
			return;
		}

		$this->install();
	}

	/**
	 * Create or extend the employee role and administrator capabilities.
	 */
	public function install(): void {
		$role = get_role( self::ROLE );

		if ( null === $role ) {
			add_role( self::ROLE, 'کارمند', array( 'read' => true ) );
			$role = get_role( self::ROLE );
		}

		if ( null !== $role ) {
			$role->add_cap( self::CREATE_BOOKINGS );
			$role->add_cap( self::VIEW_OWN_BOOKINGS );
			$role->add_cap( self::VIEW_AVAILABILITY );
		}

		$administrator = get_role( 'administrator' );

		if ( null !== $administrator ) {
			$administrator->add_cap( self::CREATE_BOOKINGS );
			$administrator->add_cap( self::VIEW_OWN_BOOKINGS );
			$administrator->add_cap( self::VIEW_AVAILABILITY );
			$administrator->add_cap( self::MANAGE_BOOKINGS );
		}

		if ( false === get_option( self::VERSION_OPTION, false ) ) {
			add_option( self::VERSION_OPTION, self::ACCESS_VERSION, '', false );
			return;
		}

		update_option( self::VERSION_OPTION, self::ACCESS_VERSION, false );
	}
}
