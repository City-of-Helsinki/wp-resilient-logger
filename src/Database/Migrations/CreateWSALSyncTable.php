<?php

namespace CityOfHelsinki\WP\ResilientLogger\Database\Migrations;

use CityOfHelsinki\WP\ResilientLogger\Database\ResilientLoggerTables;
use wpdb;

final class CreateWSALSyncTable
{
	public function __construct(
		private wpdb $db
	) {}

	public function up(): void
	{
		$table = ResilientLoggerTables::wsal_sync($this->db);

		\dbDelta("CREATE TABLE {$table} (
            occurrence_id bigint(20) NOT NULL,
            is_sent tinyint(1) NOT NULL DEFAULT 0,
            sent_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (occurrence_id)
        ) {$this->db->get_charset_collate()};");
	}
}
