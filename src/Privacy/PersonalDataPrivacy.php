<?php
/**
 * WordPress personal-data privacy integration.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Privacy;

use WorkshopRegistration\Infrastructure\Database\Tables;
use wpdb;

/**
 * Exports and anonymizes employee booking personal data on explicit requests.
 */
final class PersonalDataPrivacy {
	private const EXPORT_PAGE_SIZE = 50;

	/**
	 * Create privacy integration.
	 *
	 * @param wpdb   $database WordPress database connection.
	 * @param Tables $tables   Plugin table names.
	 */
	public function __construct( private wpdb $database, private Tables $tables ) {
	}

	/**
	 * Register privacy hooks.
	 */
	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'registerExporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'registerEraser' ) );
		add_action( 'admin_init', array( $this, 'addPolicyContent' ) );
	}

	/**
	 * Register the plugin exporter.
	 *
	 * @param array<string, array<string, mixed>> $exporters Existing exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public function registerExporter( array $exporters ): array {
		$exporters['workshop-registration'] = array(
			'exporter_friendly_name' => 'رزرو اتاق جلسه',
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Register the plugin eraser.
	 *
	 * @param array<string, array<string, mixed>> $erasers Existing erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public function registerEraser( array $erasers ): array {
		$erasers['workshop-registration'] = array(
			'eraser_friendly_name' => 'رزرو اتاق جلسه',
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Export one page of matching booking records.
	 *
	 * @param string $email_address WordPress privacy request email.
	 * @param int    $page          One-based exporter page.
	 * @return array{data:list<array<string, mixed>>,done:bool}
	 */
	public function export( string $email_address, int $page = 1 ): array {
		$user       = get_user_by( 'email', $email_address );
		$user_id    = false === $user ? 0 : (int) $user->ID;
		$conditions = array( 'email = %s' );
		$parameters = array( $this->tables->requests(), $email_address );

		if ( $user_id > 0 ) {
			$conditions[] = 'requester_user_id = %d';
			$parameters[] = $user_id;
		}

		$parameters[] = self::EXPORT_PAGE_SIZE;
		$parameters[] = max( 0, ( $page - 1 ) * self::EXPORT_PAGE_SIZE );
		$where        = implode( ' OR ', $conditions );
		$query        = $this->database->prepare(
			"SELECT id, public_reference, first_name, last_name, mobile, email, workshop_title,
			workshop_date, start_time, end_time, description, status, slot_number, created_at
			FROM %i WHERE {$where} ORDER BY id ASC LIMIT %d OFFSET %d",
			...$parameters
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->database->get_results( $query, ARRAY_A );
		$rows = is_array( $rows ) ? $rows : array();
		$data = array();

		foreach ( $rows as $row ) {
			$data[] = array(
				'group_id'    => 'workshop-registration',
				'group_label' => 'درخواست‌های رزرو اتاق جلسه',
				'item_id'     => 'workshop-request-' . (int) $row['id'],
				'data'        => array(
					array(
						'name'  => 'شناسه درخواست',
						'value' => (string) $row['public_reference'],
					),
					array(
						'name'  => 'نام',
						'value' => (string) $row['first_name'],
					),
					array(
						'name'  => 'نام خانوادگی',
						'value' => (string) $row['last_name'],
					),
					array(
						'name'  => 'موبایل',
						'value' => (string) $row['mobile'],
					),
					array(
						'name'  => 'ایمیل',
						'value' => (string) $row['email'],
					),
					array(
						'name'  => 'عنوان جلسه',
						'value' => (string) $row['workshop_title'],
					),
					array(
						'name'  => 'تاریخ جلسه',
						'value' => (string) $row['workshop_date'],
					),
					array(
						'name'  => 'ساعت شروع',
						'value' => (string) $row['start_time'],
					),
					array(
						'name'  => 'ساعت پایان',
						'value' => (string) $row['end_time'],
					),
					array(
						'name'  => 'توضیحات',
						'value' => (string) $row['description'],
					),
					array(
						'name'  => 'وضعیت',
						'value' => (string) $row['status'],
					),
					array(
						'name'  => 'شماره اتاق',
						'value' => null === $row['slot_number'] ? '' : (string) $row['slot_number'],
					),
					array(
						'name'  => 'تاریخ ثبت',
						'value' => (string) $row['created_at'] . ' UTC',
					),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => count( $rows ) < self::EXPORT_PAGE_SIZE,
		);
	}

	/**
	 * Anonymize matching identity and free-text fields while retaining schedules.
	 *
	 * @param string $email_address WordPress privacy request email.
	 * @param int    $page          Eraser page, unused because updates are atomic by identity.
	 * @return array{items_removed:bool,items_retained:bool,messages:list<string>,done:bool}
	 */
	public function erase( string $email_address, int $page = 1 ): array {
		unset( $page );
		$user       = get_user_by( 'email', $email_address );
		$user_id    = false === $user ? 0 : (int) $user->ID;
		$conditions = array( 'email = %s' );
		$parameters = array(
			$this->tables->requests(),
			0,
			'حذف‌شده',
			'حذف‌شده',
			'',
			'',
			'',
			'جلسه حذف‌شده',
			'',
			$email_address,
		);

		if ( $user_id > 0 ) {
			$conditions[] = 'requester_user_id = %d';
			$parameters[] = $user_id;
		}

		$where = implode( ' OR ', $conditions );
		$query = (string) $this->database->prepare(
			"UPDATE %i SET requester_user_id = %d, first_name = %s, last_name = %s,
			mobile = %s, mobile_normalized = %s, email = %s, workshop_title = %s, description = %s
			WHERE {$where}",
			...$parameters
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$removed = $this->database->query( $query );

		if ( false === $removed ) {
			return array(
				'items_removed'  => false,
				'items_retained' => true,
				'messages'       => array( 'ناشناس‌سازی درخواست‌های رزرو انجام نشد.' ),
				'done'           => true,
			);
		}

		$reviewer_removed = false;

		if ( $user_id > 0 ) {
			$reviewer_result  = $this->eraseReviewerIdentity( $user_id );
			$reviewer_removed = $reviewer_result['removed'];

			if ( ! $reviewer_result['success'] ) {
				return array(
					'items_removed'  => $removed > 0 || $reviewer_removed,
					'items_retained' => true,
					'messages'       => array( 'حذف شناسه بررسی‌کننده از سابقه رزرو انجام نشد.' ),
					'done'           => true,
				);
			}
		}

		return array(
			'items_removed'  => $removed > 0 || $reviewer_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Add suggested privacy-policy disclosure text.
	 */
	public function addPolicyContent(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		wp_add_privacy_policy_content(
			'رزرو اتاق جلسه',
			'<p>این افزونه نام، نام خانوادگی، شماره موبایل، ایمیل، عنوان و توضیحات جلسه، زمان رزرو و شناسه حساب کاربری را برای مدیریت درخواست و تخصیص اتاق ذخیره می‌کند. مدیران مجاز شرکت می‌توانند این اطلاعات را مشاهده کنند. در پاسخ به درخواست حذف اطلاعات شخصی، داده‌های هویتی و متن‌های آزاد ناشناس می‌شوند و زمان‌بندی و سابقه غیرهویتی برای یکپارچگی رزرو اتاق باقی می‌ماند.</p>'
		);
	}

	/**
	 * Remove an erased administrator identity from audit references.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array{success:bool,removed:bool}
	 */
	private function eraseReviewerIdentity( int $user_id ): array {
		$request_query = (string) $this->database->prepare(
			'UPDATE %i SET reviewed_by = NULL WHERE reviewed_by = %d',
			$this->tables->requests(),
			$user_id
		);
		$history_query = (string) $this->database->prepare(
			'UPDATE %i SET actor_user_id = NULL WHERE actor_user_id = %d',
			$this->tables->statusHistory(),
			$user_id
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$request_result = $this->database->query( $request_query );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$history_result = $this->database->query( $history_query );

		return array(
			'success' => false !== $request_result && false !== $history_result,
			'removed' => ( false !== $request_result && $request_result > 0 ) || ( false !== $history_result && $history_result > 0 ),
		);
	}
}
