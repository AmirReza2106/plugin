<?php
/**
 * Employee booking input validator.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Employee;

use WorkshopRegistration\Application\Registration\RegistrationData;

/**
 * Normalizes bounded employee-supplied booking fields.
 */
final class BookingInputValidator {
	/**
	 * Validate employee form fields.
	 *
	 * @param array<string, mixed> $input         Unslashed form input.
	 * @param string               $site_timezone Trusted WordPress timezone.
	 * @throws BookingValidationFailure When a field is missing or invalid.
	 */
	public function validate( array $input, string $site_timezone ): RegistrationData {
		$first_name     = $this->text( $input, 'first_name', 100 );
		$last_name      = $this->text( $input, 'last_name', 100 );
		$mobile         = $this->text( $input, 'mobile', 32 );
		$email          = $this->text( $input, 'email', 254 );
		$workshop_title = $this->text( $input, 'workshop_title', 200 );
		$workshop_date  = $this->scalar( $input, 'workshop_date', 10 );
		$start_time     = $this->scalar( $input, 'start_time', 5 );
		$end_time       = $this->scalar( $input, 'end_time', 5 );
		$description    = sanitize_textarea_field( $this->scalar( $input, 'description', 2000 ) );
		$normalized     = $this->normalizeMobile( $mobile );

		if ( false === is_email( $email ) ) {
			throw new BookingValidationFailure( 'invalid_email' );
		}

		if ( 1 !== preg_match( '/\A\+?[0-9]{8,15}\z/', $normalized ) ) {
			throw new BookingValidationFailure( 'invalid_mobile' );
		}

		return new RegistrationData(
			$first_name,
			$last_name,
			$mobile,
			$normalized,
			$email,
			$workshop_title,
			$workshop_date,
			$start_time,
			$end_time,
			$site_timezone,
			$description
		);
	}

	/**
	 * Read a required, bounded, single-line field.
	 *
	 * @param array<string, mixed> $input      Form input.
	 * @param string               $key        Field key.
	 * @param int                  $max_length Maximum character length.
	 * @throws BookingValidationFailure When the field is invalid.
	 */
	private function text( array $input, string $key, int $max_length ): string {
		$value = $this->scalar( $input, $key, $max_length );

		if ( 1 === preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) ) {
			throw new BookingValidationFailure( 'invalid_input' );
		}

		$value = sanitize_text_field( $value );

		if ( '' === $value ) {
			throw new BookingValidationFailure( 'missing_fields' );
		}

		return $value;
	}

	/**
	 * Read a required bounded scalar field.
	 *
	 * @param array<string, mixed> $input      Form input.
	 * @param string               $key        Field key.
	 * @param int                  $max_length Maximum character length.
	 * @throws BookingValidationFailure When the field is invalid.
	 */
	private function scalar( array $input, string $key, int $max_length ): string {
		if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) {
			throw new BookingValidationFailure( 'missing_fields' );
		}

		$value = trim( $input[ $key ] );

		if ( '' === $value ) {
			throw new BookingValidationFailure( 'missing_fields' );
		}

		if ( mb_strlen( $value ) > $max_length ) {
			throw new BookingValidationFailure( 'input_too_long' );
		}

		return $value;
	}

	/**
	 * Normalize Persian/Arabic digits and display punctuation in mobile numbers.
	 *
	 * @param string $mobile Submitted mobile number.
	 */
	private function normalizeMobile( string $mobile ): string {
		$mobile = strtr(
			$mobile,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
			)
		);

		return (string) preg_replace( '/[\s()\-.]/u', '', $mobile );
	}
}
