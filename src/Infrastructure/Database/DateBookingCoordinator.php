<?php
/**
 * MariaDB booking coordinator.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

use Throwable;
use WorkshopRegistration\Application\Contracts\BookingCoordinator;
use WorkshopRegistration\Application\Exception\BookingLockTimeout;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use wpdb;

/**
 * Serializes same-date allocations with a named lock and transaction.
 */
final class DateBookingCoordinator implements BookingCoordinator {
	/**
	 * Create the database booking coordinator.
	 *
	 * @param wpdb   $database       WordPress database connection.
	 * @param string $lock_namespace Site-specific lock namespace.
	 * @param int    $timeout_seconds Named-lock timeout.
	 */
	public function __construct(
		private wpdb $database,
		private string $lock_namespace,
		private int $timeout_seconds = 5
	) {
	}

	/**
	 * Execute one operation under a date lock and database transaction.
	 *
	 * @template T
	 * @param string       $workshop_date Workshop date in Y-m-d format.
	 * @param callable():T $operation    Operation to execute atomically.
	 * @return T
	 * @throws BookingLockTimeout When the date lock cannot be acquired in time.
	 * @throws PersistenceFailure When transaction or lock operations fail.
	 */
	public function run( string $workshop_date, callable $operation ): mixed {
		$lock_name = $this->lockName( $workshop_date );
		$this->acquireLock( $lock_name );

		$result         = null;
		$failure        = null;
		$in_transaction = false;

		try {
			$this->executeStatement( 'START TRANSACTION' );
			$in_transaction = true;
			$result         = $operation();
			$this->executeStatement( 'COMMIT' );
			$in_transaction = false;
		} catch ( Throwable $exception ) {
			$failure = $exception;

			if ( $in_transaction ) {
				// Preserve the original operation failure if rollback also fails.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$this->database->query( 'ROLLBACK' );
			}
		}

		$released = $this->releaseLock( $lock_name );

		if ( null !== $failure ) {
			throw $failure;
		}

		if ( ! $released ) {
			throw new PersistenceFailure( 'The booking lock could not be released.' );
		}

		return $result;
	}

	/**
	 * Build a bounded, site-specific advisory lock name.
	 *
	 * @param string $workshop_date Workshop date in Y-m-d format.
	 */
	private function lockName( string $workshop_date ): string {
		return 'workshop_booking_' . substr( hash( 'sha256', $this->lock_namespace . '|' . $workshop_date ), 0, 40 );
	}

	/**
	 * Acquire the date advisory lock.
	 *
	 * @param string $lock_name Advisory lock name.
	 * @throws BookingLockTimeout When the lock times out.
	 * @throws PersistenceFailure When the lock query fails.
	 */
	private function acquireLock( string $lock_name ): void {
		$query = $this->database->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, $this->timeout_seconds );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->database->get_var( $query );

		if ( '1' === (string) $result ) {
			return;
		}

		if ( '' !== $this->database->last_error ) {
			throw new PersistenceFailure( 'The booking lock query failed.' );
		}

		throw new BookingLockTimeout( 'The booking date is currently busy.' );
	}

	/**
	 * Release the date advisory lock.
	 *
	 * @param string $lock_name Advisory lock name.
	 */
	private function releaseLock( string $lock_name ): bool {
		$query = $this->database->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->database->get_var( $query );

		return '1' === (string) $result;
	}

	/**
	 * Execute a fixed transaction statement.
	 *
	 * @param string $statement Fixed transaction statement.
	 * @throws PersistenceFailure When MariaDB rejects the statement.
	 */
	private function executeStatement( string $statement ): void {
		// Callers provide only fixed statements defined in this class.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $this->database->query( $statement ) ) {
			throw new PersistenceFailure( 'The booking transaction failed.' );
		}
	}
}
