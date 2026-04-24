<?php

namespace CityOfHelsinki\WP\ResilientLogger\Database\Migrations;

final class CreateWSALSyncTable
{
	public function __construct(
		private string $prefix,
		private string $charset
	) {}

	public function up(): void
	{
		\dbDelta("CREATE TABLE {$this->prefix}helfi_wsal_sync (
            occurrence_id bigint(20) NOT NULL,
            is_sent tinyint(1) NOT NULL DEFAULT 0,
            sent_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (occurrence_id)
        ) {$this->charset};");
	}
}
