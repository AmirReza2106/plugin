<?php
/**
 * Register workshop application service tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Application\Registration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WorkshopRegistration\Application\Contracts\BookingCoordinator;
use WorkshopRegistration\Application\Contracts\Clock;
use WorkshopRegistration\Application\Contracts\RequestIdentityGenerator;
use WorkshopRegistration\Application\Contracts\WorkshopRepository;
use WorkshopRegistration\Application\Exception\InvalidWorkshopDate;
use WorkshopRegistration\Application\Exception\NoRoomAvailable;
use WorkshopRegistration\Application\Registration\NewWorkshopRecord;
use WorkshopRegistration\Application\Registration\RegisterWorkshop;
use WorkshopRegistration\Application\Registration\RegistrationData;
use WorkshopRegistration\Application\Registration\RequestIdentity;
use WorkshopRegistration\Domain\Scheduling\RoomReservation;
use WorkshopRegistration\Domain\Scheduling\SchedulingPolicy;
use WorkshopRegistration\Domain\Scheduling\StableSlotAllocator;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Verifies atomic registration orchestration independently of WordPress.
 */
final class RegisterWorkshopTest extends TestCase {
	/**
	 * Registration allocates and persists inside the coordinated operation.
	 */
	public function test_it_persists_a_hash_and_returns_the_raw_token(): void {
		$repository  = new InMemoryWorkshopRepository();
		$coordinator = new RecordingBookingCoordinator();
		$service     = $this->service( $repository, $coordinator );

		$result = $service->register( $this->registration(), 2 );

		self::assertSame( array( '2030-05-20' ), $coordinator->dates );
		self::assertTrue( $repository->insertedInsideCoordinator );
		self::assertSame( 'private-token', $result->trackingToken );
		self::assertSame( 1, $result->slotNumber );
		self::assertSame( WorkshopStatus::Pending, $result->status );
		self::assertNotNull( $repository->record );
		self::assertSame( str_repeat( 'a', 64 ), $repository->record->trackingTokenHash );
		self::assertNotSame( $result->trackingToken, $repository->record->trackingTokenHash );
		self::assertSame( array( 41, 1, '2030-01-01 08:00:00' ), $repository->history );
	}

	/**
	 * Existing assignments remain stable and the lowest free room is used.
	 */
	public function test_it_uses_the_lowest_available_stable_slot(): void {
		$policy                   = new SchedulingPolicy();
		$repository               = new InMemoryWorkshopRepository();
		$coordinator              = new RecordingBookingCoordinator();
		$repository->reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Approved, 1 ),
		);

		$result = $this->service( $repository, $coordinator )->register( $this->registration(), 2 );

		self::assertSame( 2, $result->slotNumber );
		self::assertSame( 2, $repository->record?->slotNumber );
	}

	/**
	 * Capacity exhaustion does not generate or persist request identity data.
	 */
	public function test_capacity_exhaustion_writes_nothing(): void {
		$policy                   = new SchedulingPolicy();
		$repository               = new InMemoryWorkshopRepository();
		$coordinator              = new RecordingBookingCoordinator();
		$repository->reservations = array(
			new RoomReservation( $policy->createInterval( '09:00', '10:00' ), WorkshopStatus::Pending, 1 ),
		);

		$this->expectException( NoRoomAvailable::class );

		try {
			$this->service( $repository, $coordinator )->register( $this->registration(), 1 );
		} finally {
			self::assertNull( $repository->record );
			self::assertNull( $repository->history );
		}
	}

	/**
	 * Invalid and past dates fail before entering the coordinator.
	 */
	public function test_it_rejects_a_past_date_before_coordination(): void {
		$repository   = new InMemoryWorkshopRepository();
		$coordinator  = new RecordingBookingCoordinator();
		$registration = $this->registration( '2029-12-31' );

		$this->expectException( InvalidWorkshopDate::class );
		$this->expectExceptionMessage( InvalidWorkshopDate::PAST_DATE );

		try {
			$this->service( $repository, $coordinator )->register( $registration, 1 );
		} finally {
			self::assertSame( array(), $coordinator->dates );
		}
	}

	/**
	 * Persistence exceptions cross the service boundary without being hidden.
	 */
	public function test_history_failure_propagates_from_the_atomic_operation(): void {
		$repository              = new InMemoryWorkshopRepository();
		$repository->failHistory = true;

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'history failed' );

		$this->service( $repository, new RecordingBookingCoordinator() )->register( $this->registration(), 1 );
	}

	/**
	 * Build the application service with deterministic collaborators.
	 *
	 * @param InMemoryWorkshopRepository  $repository  In-memory persistence gateway.
	 * @param RecordingBookingCoordinator $coordinator Recorded atomic coordinator.
	 */
	private function service(
		InMemoryWorkshopRepository $repository,
		RecordingBookingCoordinator $coordinator
	): RegisterWorkshop {
		$repository->coordinator = $coordinator;

		return new RegisterWorkshop(
			$repository,
			$coordinator,
			new FixedIdentityGenerator(),
			new FixedClock(),
			new SchedulingPolicy(),
			new StableSlotAllocator()
		);
	}

	/**
	 * Build valid normalized registration input.
	 *
	 * @param string $date Workshop date.
	 */
	private function registration( string $date = '2030-05-20' ): RegistrationData {
		return new RegistrationData(
			'Jane',
			'Doe',
			'+1 555 0100',
			'+15550100',
			'jane@example.test',
			'Planning',
			$date,
			'09:30',
			'10:00',
			'UTC',
			'Quarterly planning'
		);
	}
}

/**
 * Executes operations immediately while recording their boundary.
 */
final class RecordingBookingCoordinator implements BookingCoordinator {
	/** @var list<string> */
	public array $dates = array();

	public bool $running = false;

	/**
	 * Execute one recorded operation.
	 *
	 * @template T
	 * @param string       $workshop_date Workshop date.
	 * @param callable():T $operation    Operation callback.
	 * @return T
	 */
	public function run( string $workshop_date, callable $operation ): mixed {
		$this->dates[] = $workshop_date;
		$this->running = true;

		try {
			return $operation();
		} finally {
			$this->running = false;
		}
	}
}

/**
 * Stores application writes in memory for orchestration assertions.
 */
final class InMemoryWorkshopRepository implements WorkshopRepository {
	/** @var list<RoomReservation> */
	public array $reservations = array();

	public ?NewWorkshopRecord $record = null;

	/** @var array{int, int, string}|null */
	public ?array $history = null;

	public bool $insertedInsideCoordinator = false;

	public bool $failHistory = false;

	public ?RecordingBookingCoordinator $coordinator = null;

	/** @return list<RoomReservation> */
	public function findActiveReservationsByDate( string $workshop_date ): array {
		unset( $workshop_date );
		return $this->reservations;
	}

	public function insert( NewWorkshopRecord $record ): int {
		$this->record                    = $record;
		$this->insertedInsideCoordinator = null === $this->coordinator || $this->coordinator->running;
		return 41;
	}

	public function insertInitialHistory( int $request_id, int $slot_number, string $created_at ): void {
		if ( $this->failHistory ) {
			throw new RuntimeException( 'history failed' );
		}

		$this->history = array( $request_id, $slot_number, $created_at );
	}
}

/**
 * Provides deterministic request identity data.
 */
final class FixedIdentityGenerator implements RequestIdentityGenerator {
	public function generate(): RequestIdentity {
		return new RequestIdentity( 'public-reference', 'private-token', str_repeat( 'a', 64 ) );
	}
}

/**
 * Provides a deterministic current time.
 */
final class FixedClock implements Clock {
	public function now( DateTimeZone $timezone ): DateTimeImmutable {
		return new DateTimeImmutable( '2030-01-01 08:00:00', $timezone );
	}
}
