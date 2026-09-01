<?php
/**
 * WordPress scheduling settings storage.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Settings;

use Throwable;
use WorkshopRegistration\Domain\Scheduling\SchedulingRules;

/**
 * Loads validated scheduling rules and room capacity from one option.
 */
final class SchedulingSettings {
	public const OPTION_NAME = 'workshop_registration_settings';

	/**
	 * Get the complete normalized settings array.
	 *
	 * @return array{workday_start: string, workday_end: string, minimum_duration: int, maximum_duration: int, room_capacity: int}
	 */
	public function all(): array {
		$defaults = self::defaults();
		$stored   = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			return $defaults;
		}

		$settings = array(
			'workday_start'    => isset( $stored['workday_start'] ) && is_string( $stored['workday_start'] ) ? $stored['workday_start'] : $defaults['workday_start'],
			'workday_end'      => isset( $stored['workday_end'] ) && is_string( $stored['workday_end'] ) ? $stored['workday_end'] : $defaults['workday_end'],
			'minimum_duration' => isset( $stored['minimum_duration'] ) ? (int) $stored['minimum_duration'] : $defaults['minimum_duration'],
			'maximum_duration' => isset( $stored['maximum_duration'] ) ? (int) $stored['maximum_duration'] : $defaults['maximum_duration'],
			'room_capacity'    => isset( $stored['room_capacity'] ) ? (int) $stored['room_capacity'] : $defaults['room_capacity'],
		);

		try {
			$this->rulesFromSettings( $settings );
		} catch ( Throwable ) {
			return $defaults;
		}

		if ( $settings['room_capacity'] < 1 || $settings['room_capacity'] > 100 ) {
			return $defaults;
		}

		return $settings;
	}

	/**
	 * Get the current domain scheduling rules.
	 */
	public function rules(): SchedulingRules {
		return $this->rulesFromSettings( $this->all() );
	}

	/**
	 * Get configured numbered room capacity.
	 */
	public function roomCapacity(): int {
		return $this->all()['room_capacity'];
	}

	/**
	 * Add defaults as a non-autoloaded option when missing.
	 */
	public function installDefaults(): void {
		if ( false === get_option( self::OPTION_NAME, false ) ) {
			add_option( self::OPTION_NAME, self::defaults(), '', false );
		}
	}

	/**
	 * Get initial company settings.
	 *
	 * @return array{workday_start: string, workday_end: string, minimum_duration: int, maximum_duration: int, room_capacity: int}
	 */
	public static function defaults(): array {
		return array(
			'workday_start'    => '09:00',
			'workday_end'      => '18:00',
			'minimum_duration' => 30,
			'maximum_duration' => 60,
			'room_capacity'    => 1,
		);
	}

	/**
	 * Build domain rules from normalized settings.
	 *
	 * @param array{workday_start: string, workday_end: string, minimum_duration: int, maximum_duration: int, room_capacity: int} $settings Settings array.
	 */
	private function rulesFromSettings( array $settings ): SchedulingRules {
		return new SchedulingRules(
			$this->timeToMinute( $settings['workday_start'] ),
			$this->timeToMinute( $settings['workday_end'] ),
			$settings['minimum_duration'],
			$settings['maximum_duration']
		);
	}

	/**
	 * Convert strict HH:MM settings to minutes after midnight.
	 *
	 * @param string $time Stored time setting.
	 * @throws \InvalidArgumentException When the time is invalid.
	 */
	private function timeToMinute( string $time ): int {
		if ( 1 !== preg_match( '/\A(?:[01]\d|2[0-3]):[0-5]\d\z/', $time ) ) {
			throw new \InvalidArgumentException( 'Invalid scheduling time.' );
		}

		$parts = array_map( 'intval', explode( ':', $time ) );

		return ( $parts[0] * 60 ) + $parts[1];
	}
}
