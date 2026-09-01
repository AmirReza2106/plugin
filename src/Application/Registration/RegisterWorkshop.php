<?php
/**
 * Register workshop application service.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\Registration;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use WorkshopRegistration\Application\Contracts\BookingCoordinator;
use WorkshopRegistration\Application\Contracts\Clock;
use WorkshopRegistration\Application\Contracts\PublicReferenceGenerator;
use WorkshopRegistration\Application\Contracts\WorkshopRepository;
use WorkshopRegistration\Application\Exception\InvalidWorkshopDate;
use WorkshopRegistration\Application\Exception\NoRoomAvailable;
use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;
use WorkshopRegistration\Domain\Scheduling\StableSlotAllocator;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Atomically validates, allocates, and persists one workshop request.
 */
final class RegisterWorkshop {
	/**
	 * Create the registration service.
	 *
	 * @param WorkshopRepository       $repository         Workshop persistence gateway.
	 * @param BookingCoordinator       $coordinator        Atomic booking coordinator.
	 * @param PublicReferenceGenerator $reference_generator Public reference generator.
	 * @param Clock                    $clock               Application clock.
	 * @param SchedulingPolicy         $scheduling_policy   Booking-time policy.
	 * @param StableSlotAllocator      $slot_allocator      Stable room allocator.
	 */
	public function __construct(
		private WorkshopRepository $repository,
		private BookingCoordinator $coordinator,
		private PublicReferenceGenerator $reference_generator,
		private Clock $clock,
		private SchedulingPolicy $scheduling_policy,
		private StableSlotAllocator $slot_allocator
	) {
	}

	/**
	 * Register a pending workshop and return its private tracking credential.
	 *
	 * @param RegistrationData $registration    Normalized registration data.
	 * @param int              $requester_user_id Authenticated employee user ID.
	 * @param int              $capacity        Configured room capacity.
	 * @throws InvalidArgumentException When capacity is not positive.
	 * @throws InvalidWorkshopDate When the date or timezone is invalid.
	 * @throws NoRoomAvailable When all stable rooms conflict.
	 */
	public function register( RegistrationData $registration, int $requester_user_id, int $capacity ): RegistrationResult {
		if ( $requester_user_id < 1 ) {
			throw new InvalidArgumentException( 'Requester user ID must be positive.' );
		}

		if ( $capacity < 1 ) {
			throw new InvalidArgumentException( 'Room capacity must be positive.' );
		}

		$this->validateDate( $registration );
		$interval = $this->scheduling_policy->createInterval( $registration->startTime, $registration->endTime );

		return $this->coordinator->run(
			$registration->workshopDate,
			function () use ( $registration, $requester_user_id, $capacity, $interval ): RegistrationResult {
				$reservations = $this->repository->findActiveReservationsByDate( $registration->workshopDate );
				$slot_number  = $this->slot_allocator->allocate( $interval, $reservations, $capacity );

				if ( null === $slot_number ) {
					throw new NoRoomAvailable( 'No room is available for the requested time.' );
				}

				$public_reference = $this->reference_generator->generate();
				$created_at       = $this->clock->now( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
				$record           = new NewWorkshopRecord(
					$registration,
					$requester_user_id,
					$public_reference,
					$slot_number,
					$created_at
				);

				$request_id = $this->repository->insert( $record );
				$this->repository->insertInitialHistory( $request_id, $slot_number, $created_at );

				return new RegistrationResult(
					$request_id,
					$public_reference,
					$slot_number,
					WorkshopStatus::Pending
				);
			}
		);
	}

	/**
	 * Validate a strict, non-past local workshop date.
	 *
	 * @param RegistrationData $registration Registration containing date and timezone.
	 * @throws InvalidWorkshopDate When the date or timezone is invalid.
	 */
	private function validateDate( RegistrationData $registration ): void {
		try {
			$timezone = new DateTimeZone( $registration->siteTimezone );
		} catch ( Exception ) {
			throw new InvalidWorkshopDate( InvalidWorkshopDate::INVALID_TIMEZONE );
		}

		$date   = DateTimeImmutable::createFromFormat( '!Y-m-d', $registration->workshopDate, $timezone );
		$errors = DateTimeImmutable::getLastErrors();

		if (
			false === $date
			|| $date->format( 'Y-m-d' ) !== $registration->workshopDate
			|| ( false !== $errors && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) )
		) {
			throw new InvalidWorkshopDate( InvalidWorkshopDate::INVALID_FORMAT );
		}

		$today = $this->clock->now( $timezone )->setTime( 0, 0 );

		if ( $date < $today ) {
			throw new InvalidWorkshopDate( InvalidWorkshopDate::PAST_DATE );
		}
	}
}
