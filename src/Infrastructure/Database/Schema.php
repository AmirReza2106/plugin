<?php
/**
 * Plugin database schema definition.
 *
 * @package WorkshopRegistration
 */

declare(strict_types=1);

namespace WorkshopRegistration\Infrastructure\Database;

/**
 * Builds the custom table DDL consumed by WordPress dbDelta().
 */
final class Schema {
	/**
	 * Build all schema statements.
	 *
	 * @param Tables $tables          Plugin table names.
	 * @param string $charset_collate WordPress charset and collation clause.
	 * @return list<string>
	 */
	public function statements( Tables $tables, string $charset_collate ): array {
		$requests_table = $tables->requests();
		$history_table  = $tables->statusHistory();

		return array(
			"CREATE TABLE {$requests_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_reference char(36) NOT NULL,
				tracking_token_hash char(64) NOT NULL,
				first_name varchar(100) NOT NULL,
				last_name varchar(100) NOT NULL,
				mobile varchar(32) NOT NULL,
				mobile_normalized varchar(20) NOT NULL,
				email varchar(254) NOT NULL,
				workshop_title varchar(200) NOT NULL,
				workshop_date date NOT NULL,
				start_time time NOT NULL,
				end_time time NOT NULL,
				site_timezone varchar(64) NOT NULL,
				description text NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'pending',
				slot_number smallint(5) unsigned DEFAULT NULL,
				reviewed_by bigint(20) unsigned DEFAULT NULL,
				status_changed_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_reference (public_reference),
				UNIQUE KEY tracking_token_hash (tracking_token_hash),
				KEY date_status (workshop_date,status),
				KEY allocation_lookup (workshop_date,slot_number,status,start_time,end_time),
				KEY mobile_normalized (mobile_normalized),
				KEY requester_name (last_name,first_name),
				KEY email (email),
				KEY created_at (created_at)
			) {$charset_collate};",
			"CREATE TABLE {$history_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				request_id bigint(20) unsigned NOT NULL,
				from_status varchar(20) DEFAULT NULL,
				to_status varchar(20) NOT NULL,
				previous_slot_number smallint(5) unsigned DEFAULT NULL,
				new_slot_number smallint(5) unsigned DEFAULT NULL,
				actor_user_id bigint(20) unsigned DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY request_history (request_id,created_at),
				KEY actor_user_id (actor_user_id)
			) {$charset_collate};",
		);
	}
}
