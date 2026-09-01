<?php
/**
 * Internal registration service composition.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Registration;

use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;
use WorkshopRegistration\Domain\Scheduling\StableSlotAllocator;
use WorkshopRegistration\Infrastructure\Database\DateBookingCoordinator;
use WorkshopRegistration\Infrastructure\Database\Tables;
use WorkshopRegistration\Infrastructure\Database\WordPressWorkshopRepository;
use WorkshopRegistration\Infrastructure\Security\UuidPublicReferenceGenerator;
use WorkshopRegistration\Infrastructure\Settings\SchedulingSettings;
use WorkshopRegistration\Infrastructure\Time\SystemClock;
use wpdb;

/**
 * Builds employee registration services from current WordPress settings.
 */
final class RegistrationServiceFactory {
	/**
	 * Create the registration factory.
	 *
	 * @param wpdb               $database WordPress database connection.
	 * @param SchedulingSettings $settings Current scheduling settings.
	 */
	public function __construct( private wpdb $database, private SchedulingSettings $settings ) {
	}

	/**
	 * Build the transactional registration service.
	 */
	public function create(): RegisterWorkshop {
		$policy = new SchedulingPolicy( $this->settings->rules() );

		return new RegisterWorkshop(
			new WordPressWorkshopRepository( $this->database, new Tables( $this->database->prefix ) ),
			new DateBookingCoordinator( $this->database, $this->database->prefix ),
			new UuidPublicReferenceGenerator(),
			new SystemClock(),
			$policy,
			new StableSlotAllocator()
		);
	}
}
