<?php

namespace CityOfHelsinki\WP\ResilientLogger\Database\Migrations;

final class CreateResilientLoggerTable
{
	public function __construct(
		private string $prefix,
		private string $charset
	) {}

	public function up(): void
	{
		\dbDelta("CREATE TABLE {$this->prefix}helfi_resilient_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level int(11) NOT NULL,
            message longtext NOT NULL,
            context longtext,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            is_sent tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY is_sent_created_at (is_sent, created_at)
        ) {$this->charset};");
	}
}
