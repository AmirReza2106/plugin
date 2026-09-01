<?php
/**
 * Administrator request decision use case.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Application\AdminRequests;

use DateTimeZone;
use InvalidArgumentException;
use WorkshopRegistration\Application\Contracts\Clock;
use WorkshopRegistration\Application\Contracts\RequestDecisionGateway;
use WorkshopRegistration\Domain\WorkshopStatus;

/**
 * Validates and timestamps final administrator decisions.
 */
final class DecideRequest {
	/**
	 * Create the decision use case.
	 *
	 * @param RequestDecisionGateway $gateway Decision persistence gateway.
	 * @param Clock                  $clock   Application clock.
	 */
	public function __construct( private RequestDecisionGateway $gateway, private Clock $clock ) {
	}

	/**
	 * Finalize one pending request.
	 *
	 * @param int            $request_id   Request database ID.
	 * @param WorkshopStatus $target        Approved or rejected status.
	 * @param int            $actor_user_id Administrator user ID.
	 * @throws InvalidArgumentException When identifiers or target status are invalid.
	 */
	public function execute( int $request_id, WorkshopStatus $target, int $actor_user_id ): void {
		if (
			$request_id < 1
			|| $actor_user_id < 1
			|| ! in_array( $target, array( WorkshopStatus::Approved, WorkshopStatus::Rejected ), true )
		) {
			throw new InvalidArgumentException( 'Request decision parameters are invalid.' );
		}

		$this->gateway->decide(
			$request_id,
			$target,
			$actor_user_id,
			$this->clock->now( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' )
		);
	}
}
