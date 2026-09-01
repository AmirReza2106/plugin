<?php
/**
 * Employee dashboard one-time notice storage.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Employee;

use WorkshopRegistration\Application\Registration\RegistrationResult;

/**
 * Carries success/error state through PRG without personal query parameters.
 */
final class EmployeeNoticeStore {
	private const LIFETIME_SECONDS = 300;

	/**
	 * Store a success result for one employee.
	 *
	 * @param int                $employee_user_id Owning employee user ID.
	 * @param RegistrationResult $result           Successful registration result.
	 */
	public function createSuccess( int $employee_user_id, RegistrationResult $result ): string {
		return $this->create(
			$employee_user_id,
			array(
				'type'      => 'success',
				'reference' => $result->publicReference,
				'room'      => $result->slotNumber,
			)
		);
	}

	/**
	 * Store an allow-listed error for one employee.
	 *
	 * @param int    $employee_user_id Owning employee user ID.
	 * @param string $code             Safe error code.
	 */
	public function createError( int $employee_user_id, string $code ): string {
		return $this->create(
			$employee_user_id,
			array(
				'type' => 'error',
				'code' => $code,
			)
		);
	}

	/**
	 * Consume a notice only for its owning employee.
	 *
	 * @param int    $employee_user_id Owning employee user ID.
	 * @param string $receipt          Random notice receipt.
	 * @return array<string, mixed>|null
	 */
	public function consume( int $employee_user_id, string $receipt ): ?array {
		if ( 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $receipt ) ) {
			return null;
		}

		$key    = $this->key( $receipt );
		$notice = get_transient( $key );
		delete_transient( $key );

		if ( ! is_array( $notice ) || (int) ( $notice['employee_user_id'] ?? 0 ) !== $employee_user_id ) {
			return null;
		}

		unset( $notice['employee_user_id'] );

		return $notice;
	}

	/**
	 * Store a notice behind a random receipt.
	 *
	 * @param int   $employee_user_id Owning employee user ID.
	 * @param array $notice          Notice payload.
	 * @phpstan-param array<string, mixed> $notice
	 */
	private function create( int $employee_user_id, array $notice ): string {
		$receipt                    = bin2hex( random_bytes( 32 ) );
		$notice['employee_user_id'] = $employee_user_id;
		set_transient( $this->key( $receipt ), $notice, self::LIFETIME_SECONDS );

		return $receipt;
	}

	/**
	 * Hash the bearer receipt before using it as a transient key.
	 *
	 * @param string $receipt Random notice receipt.
	 */
	private function key( string $receipt ): string {
		return 'workshop_employee_notice_' . hash( 'sha256', $receipt );
	}
}
