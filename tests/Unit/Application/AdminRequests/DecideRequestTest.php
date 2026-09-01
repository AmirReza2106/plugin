<?php
/**
 * Administrator request decision use-case tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Application\AdminRequests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Application\AdminRequests\DecideRequest;
use WorkshopRegistration\Application\Contracts\Clock;
use WorkshopRegistration\Application\Contracts\RequestDecisionGateway;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Verifies final-decision validation and deterministic UTC timestamps.
 */
final class DecideRequestTest extends TestCase {
	/**
	 * Approved and rejected decisions are passed to persistence with UTC time.
	 */
	public function test_it_records_a_valid_final_decision(): void {
		$gateway = new RecordingDecisionGateway();
		$service = new DecideRequest( $gateway, new FixedDecisionClock() );

		$service->execute( 41, WorkshopStatus::Rejected, 7 );

		self::assertSame( array( 41, WorkshopStatus::Rejected, 7, '2030-05-01 08:30:00' ), $gateway->decision );
	}

	/**
	 * Pending is not a valid administrator decision target.
	 */
	public function test_it_rejects_a_non_final_target(): void {
		$this->expectException( InvalidArgumentException::class );

		( new DecideRequest( new RecordingDecisionGateway(), new FixedDecisionClock() ) )
			->execute( 41, WorkshopStatus::Pending, 7 );
	}

	/**
	 * Invalid identifiers fail before persistence.
	 */
	public function test_it_rejects_invalid_identifiers(): void {
		$gateway = new RecordingDecisionGateway();

		try {
			( new DecideRequest( $gateway, new FixedDecisionClock() ) )
				->execute( 0, WorkshopStatus::Approved, 7 );
			self::fail( 'Expected invalid request identifier failure.' );
		} catch ( InvalidArgumentException ) {
			self::assertNull( $gateway->decision );
		}
	}
}

/**
 * Records one application-layer decision.
 */
final class RecordingDecisionGateway implements RequestDecisionGateway {
	/**
	 * Most recently recorded decision.
	 *
	 * @var array{int, WorkshopStatus, int, string}|null
	 */
	public ?array $decision = null;

	/**
	 * Record one decision.
	 *
	 * @param int            $request_id   Request ID.
	 * @param WorkshopStatus $target        Target status.
	 * @param int            $actor_user_id Actor user ID.
	 * @param string         $changed_at    UTC timestamp.
	 */
	public function decide( int $request_id, WorkshopStatus $target, int $actor_user_id, string $changed_at ): void {
		$this->decision = array( $request_id, $target, $actor_user_id, $changed_at );
	}
}

/**
 * Supplies a fixed application time.
 */
final class FixedDecisionClock implements Clock {
	/**
	 * Return a fixed time in the requested timezone.
	 *
	 * @param DateTimeZone $timezone Requested timezone.
	 */
	public function now( DateTimeZone $timezone ): DateTimeImmutable {
		return new DateTimeImmutable( '2030-05-01 08:30:00', $timezone );
	}
}
