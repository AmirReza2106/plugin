<?php
/**
 * Employee booking input validator tests.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Tests\Unit\Employee;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkshopRegistration\Employee\BookingInputValidator;
use WorkshopRegistration\Employee\BookingValidationFailure;

/**
 * Verifies bounded normalization before employee data reaches the application layer.
 */
final class BookingInputValidatorTest extends TestCase {
	/**
	 * Persian mobile digits and submitted text are normalized safely.
	 */
	public function test_it_normalizes_valid_employee_input(): void {
		$result = ( new BookingInputValidator() )->validate(
			array(
				'first_name'     => '  علی  ',
				'last_name'      => 'رضایی',
				'mobile'         => '۰۹۱۲-۱۲۳-۴۵۶۷',
				'email'          => 'employee@example.test',
				'workshop_title' => ' جلسه برنامه‌ریزی ',
				'workshop_date'  => '2030-05-20',
				'start_time'     => '09:00',
				'end_time'       => '09:30',
				'description'    => " توضیحات جلسه\nداخلی ",
			),
			'Asia/Tehran'
		);

		self::assertSame( 'علی', $result->firstName );
		self::assertSame( '09121234567', $result->mobileNormalized );
		self::assertSame( 'جلسه برنامه‌ریزی', $result->workshopTitle );
		self::assertSame( 'Asia/Tehran', $result->siteTimezone );
		self::assertSame( "توضیحات جلسه\nداخلی", $result->description );
	}

	/**
	 * Invalid or attacker-controlled field shapes fail with safe error codes.
	 *
	 * @param string $field         Field to replace.
	 * @param mixed  $value         Invalid value.
	 * @param string $expected_code Expected safe failure code.
	 */
	#[DataProvider( 'invalidInputProvider' )]
	public function test_it_rejects_invalid_input( string $field, mixed $value, string $expected_code ): void {
		$input           = $this->validInput();
		$input[ $field ] = $value;

		$this->expectException( BookingValidationFailure::class );
		$this->expectExceptionMessage( $expected_code );

		( new BookingInputValidator() )->validate( $input, 'UTC' );
	}

	/**
	 * Provide invalid employee field values.
	 *
	 * @return iterable<string, array{string, mixed, string}>
	 */
	public static function invalidInputProvider(): iterable {
		yield 'missing required field' => array( 'first_name', '', 'missing_fields' );
		yield 'array injection' => array( 'workshop_title', array( 'unexpected' ), 'missing_fields' );
		yield 'invalid email' => array( 'email', 'not-an-email', 'invalid_email' );
		yield 'invalid mobile' => array( 'mobile', '1234', 'invalid_mobile' );
		yield 'oversized title' => array( 'workshop_title', str_repeat( 'a', 201 ), 'input_too_long' );
		yield 'control character' => array( 'last_name', "Bad\x01Name", 'invalid_input' );
	}

	/**
	 * Build valid employee form input.
	 *
	 * @return array<string, mixed>
	 */
	private function validInput(): array {
		return array(
			'first_name'     => 'Jane',
			'last_name'      => 'Doe',
			'mobile'         => '+15550100',
			'email'          => 'jane@example.test',
			'workshop_title' => 'Planning',
			'workshop_date'  => '2030-05-20',
			'start_time'     => '09:00',
			'end_time'       => '09:30',
			'description'    => 'Quarterly planning',
		);
	}
}
