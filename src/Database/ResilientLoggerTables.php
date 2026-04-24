<?php

namespace CityOfHelsinki\WP\ResilientLogger\Database;

use wpdb;

final class ResilientLoggerTables
{
	public static function date_time_format(): string
	{
		return 'Y-m-d H:i:s';
	}

	public static function resilient_log(wpdb $db): string
	{
		return "{$db->prefix}helfi_resilient_log";
	}

	public static function wsal_sync(wpdb $db): string
	{
		return "{$db->prefix}helfi_wsal_sync";
	}
}
