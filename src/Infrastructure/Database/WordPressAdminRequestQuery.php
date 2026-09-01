<?php
/**
 * WordPress administrator request query.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

use WorkshopRegistration\Application\AdminRequests\AdminRequestItem;
use WorkshopRegistration\Application\AdminRequests\AdminRequestPage;
use WorkshopRegistration\Application\Contracts\AdminRequestQuery;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Domain\WorkshopStatus;
use wpdb;

/**
 * Reads complete request details for authorized administrators.
 */
final class WordPressAdminRequestQuery implements AdminRequestQuery {
	/**
	 * Create the administrator request query.
	 *
	 * @param wpdb   $database WordPress database connection.
	 * @param Tables $tables   Plugin table names.
	 */
	public function __construct( private wpdb $database, private Tables $tables ) {
	}

	/**
	 * Find one filtered administrator request page.
	 *
	 * @param WorkshopStatus|null $status   Optional status filter.
	 * @param string|null         $date     Optional local meeting date.
	 * @param string              $search   Bounded search text.
	 * @param int                 $page     One-based page number.
	 * @param int                 $per_page Page size.
	 * @throws PersistenceFailure When query parameters, rows, or database operations are invalid.
	 */
	public function findPage(
		?WorkshopStatus $status,
		?string $date,
		string $search,
		int $page,
		int $per_page
	): AdminRequestPage {
		if ( $page < 1 || $per_page < 1 || $per_page > 100 || mb_strlen( $search ) > 100 ) {
			throw new PersistenceFailure( 'Administrator request query parameters are invalid.' );
		}

		$table      = $this->tables->requests();
		$conditions = array( '1 = 1' );
		$parameters = array( $table );

		if ( null !== $status ) {
			$conditions[] = 'status = %s';
			$parameters[] = $status->value;
		}

		if ( null !== $date ) {
			$conditions[] = 'workshop_date = %s';
			$parameters[] = $date;
		}

		if ( '' !== $search ) {
			$like         = '%' . $this->database->esc_like( $search ) . '%';
			$conditions[] = '(public_reference LIKE %s OR workshop_title LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR mobile_normalized LIKE %s)';
			for ( $index = 0; $index < 6; ++$index ) {
				$parameters[] = $like;
			}
		}

		$where       = implode( ' AND ', $conditions );
		$list_args   = array_merge( $parameters, array( $per_page, ( $page - 1 ) * $per_page ) );
		$list_query  = $this->database->prepare(
			"SELECT id, requester_user_id, public_reference, first_name, last_name, mobile, email,
			workshop_title, workshop_date, start_time, end_time, description, status, slot_number,
			reviewed_by, status_changed_at, created_at
			FROM %i WHERE {$where}
			ORDER BY CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,
			workshop_date ASC, start_time ASC, id ASC LIMIT %d OFFSET %d",
			...$list_args
		);
		$count_query = $this->database->prepare(
			"SELECT COUNT(*) FROM %i WHERE {$where}",
			...$parameters
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->database->get_results( $list_query, ARRAY_A );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total = $this->database->get_var( $count_query );

		if ( null === $rows || null === $total || '' !== $this->database->last_error ) {
			throw new PersistenceFailure( 'Administrator requests could not be read.' );
		}

		$counts_query = $this->database->prepare(
			'SELECT status, COUNT(*) AS total FROM %i GROUP BY status',
			$table
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$count_rows = $this->database->get_results( $counts_query, ARRAY_A );

		// The intervening query mutates wpdb::last_error, but the WordPress stub marks get_results() as pure.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// @phpstan-ignore notIdentical.alwaysFalse
		if ( null === $count_rows || '' !== $this->database->last_error ) {
			throw new PersistenceFailure( 'Administrator request counts could not be read.' );
		}

		$counts = array(
			'pending'  => 0,
			'approved' => 0,
			'rejected' => 0,
		);

		foreach ( $count_rows as $count_row ) {
			$key = (string) $count_row['status'];

			if ( array_key_exists( $key, $counts ) ) {
				$counts[ $key ] = (int) $count_row['total'];
			}
		}

		$items = array();

		foreach ( $rows as $row ) {
			$row_status = WorkshopStatus::tryFrom( (string) $row['status'] );

			if ( null === $row_status ) {
				throw new PersistenceFailure( 'A stored administrator request status is invalid.' );
			}

			$items[] = new AdminRequestItem(
				(int) $row['id'],
				(string) $row['public_reference'],
				(int) $row['requester_user_id'],
				(string) $row['first_name'],
				(string) $row['last_name'],
				(string) $row['mobile'],
				(string) $row['email'],
				(string) $row['workshop_title'],
				(string) $row['workshop_date'],
				substr( (string) $row['start_time'], 0, 5 ),
				substr( (string) $row['end_time'], 0, 5 ),
				(string) $row['description'],
				$row_status,
				null === $row['slot_number'] ? null : (int) $row['slot_number'],
				null === $row['reviewed_by'] ? null : (int) $row['reviewed_by'],
				null === $row['status_changed_at'] ? null : (string) $row['status_changed_at'],
				(string) $row['created_at']
			);
		}

		return new AdminRequestPage( $items, (int) $total, $page, $per_page, $counts );
	}
}
