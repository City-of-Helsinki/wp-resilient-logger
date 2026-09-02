<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Database;

use CityOfHelsinki\WP\ResilientLogger\Database\Actions\ResetDatabase;

final class DatabaseHooks
{
	public function reset_database(): void
	{
		global $wpdb;

		(new ResetDatabase($wpdb))->execute();
	}
}
