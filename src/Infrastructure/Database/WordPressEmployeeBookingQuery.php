<?php
/**
 * WordPress employee booking query.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

use WorkshopRegistration\Application\Contracts\EmployeeBookingQuery;
use WorkshopRegistration\Application\EmployeeDashboard\EmployeeBookingItem;
use WorkshopRegistration\Application\EmployeeDashboard\EmployeeBookingPage;
use WorkshopRegistration\Application\Exception\PersistenceFailure;
use WorkshopRegistration\Domain\WorkshopStatus;
use wpdb;

/**
 * Reads personal request details only through requester ownership predicates.
 */
final class WordPressEmployeeBookingQuery implements EmployeeBookingQuery {
	/**
	 * Create the employee booking query.
	 *
	 * @param wpdb   $database WordPress database connection.
	 * @param Tables $tables   Plugin table names.
	 */
	public function __construct( private wpdb $database, private Tables $tables ) {
	}

	/**
	 * Find one page of employee-owned requests.
	 *
	 * @param int                 $employee_user_id Authenticated employee ID.
	 * @param WorkshopStatus|null $status           Optional status filter.
	 * @param int                 $page             One-based page number.
	 * @param int                 $per_page         Page size.
	 * @throws PersistenceFailure When the query or stored row is invalid.
	 */
	public function findPage(
		int $employee_user_id,
		?WorkshopStatus $status,
		int $page,
		int $per_page
	): EmployeeBookingPage {
		if ( $employee_user_id < 1 || $page < 1 || $per_page < 1 || $per_page > 100 ) {
			throw new PersistenceFailure( 'Employee booking query parameters are invalid.' );
		}

		$table  = $this->tables->requests();
		$offset = ( $page - 1 ) * $per_page;

		if ( null === $status ) {
			$list_query  = $this->database->prepare(
				'SELECT id, public_reference, workshop_title, workshop_date, start_time, end_time, slot_number, status, created_at
				FROM %i WHERE requester_user_id = %d
				ORDER BY workshop_date DESC, start_time DESC, id DESC LIMIT %d OFFSET %d',
				$table,
				$employee_user_id,
				$per_page,
				$offset
			);
			$count_query = $this->database->prepare(
				'SELECT COUNT(*) FROM %i WHERE requester_user_id = %d',
				$table,
				$employee_user_id
			);
		} else {
			$list_query  = $this->database->prepare(
				'SELECT id, public_reference, workshop_title, workshop_date, start_time, end_time, slot_number, status, created_at
				FROM %i WHERE requester_user_id = %d AND status = %s
				ORDER BY workshop_date DESC, start_time DESC, id DESC LIMIT %d OFFSET %d',
				$table,
				$employee_user_id,
				$status->value,
				$per_page,
				$offset
			);
			$count_query = $this->database->prepare(
				'SELECT COUNT(*) FROM %i WHERE requester_user_id = %d AND status = %s',
				$table,
				$employee_user_id,
				$status->value
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->database->get_results( $list_query, ARRAY_A );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total = $this->database->get_var( $count_query );

		if ( null === $rows || null === $total || '' !== $this->database->last_error ) {
			throw new PersistenceFailure( 'Employee bookings could not be read.' );
		}

		$counts_query = $this->database->prepare(
			'SELECT status, COUNT(*) AS total FROM %i WHERE requester_user_id = %d GROUP BY status',
			$table,
			$employee_user_id
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$count_rows = $this->database->get_results( $counts_query, ARRAY_A );

		// The intervening query mutates wpdb::last_error, but the WordPress stub marks get_results() as pure.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// @phpstan-ignore notIdentical.alwaysFalse
		if ( null === $count_rows || '' !== $this->database->last_error ) {
			throw new PersistenceFailure( 'Employee booking counts could not be read.' );
		}

		$items  = array();
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

		foreach ( $rows as $row ) {
			$row_status = WorkshopStatus::tryFrom( (string) $row['status'] );

			if ( null === $row_status ) {
				throw new PersistenceFailure( 'A stored employee booking status is invalid.' );
			}

			$items[] = new EmployeeBookingItem(
				(int) $row['id'],
				(string) $row['public_reference'],
				(string) $row['workshop_title'],
				(string) $row['workshop_date'],
				substr( (string) $row['start_time'], 0, 5 ),
				substr( (string) $row['end_time'], 0, 5 ),
				null === $row['slot_number'] ? null : (int) $row['slot_number'],
				$row_status,
				(string) $row['created_at']
			);
		}

		return new EmployeeBookingPage( $items, (int) $total, $page, $per_page, $counts );
	}
}
