<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Actions;

use CityOfHelsinki\WP\ResilientLogger\Helpers\ResilientLoggerException;
use WSAL\Controllers\Alert_Manager;
use WSAL\Controllers\Database_Manager;
use WSAL\Entities\Occurrences_Entity;

final class ResetWSALDatabase
{
	private const ACTIVITY_LOG_PURGED = 6034;

	public function execute(): void
	{
		if ( $this->can_execute() ) {
			$activities_count = Occurrences_Entity::count_records();

			if ( ! Database_Manager::purge_activity() ) {
				throw ResilientLoggerException::db_reset_failed( 'WSAL' );
			}

			Alert_Manager::trigger_event(
				self::ACTIVITY_LOG_PURGED,
				array( 'PurgedCount' => $activities_count )
			);
		}
	}

	private function can_execute(): bool
	{
		return class_exists( Alert_Manager::class )
			&& class_exists( Database_Manager::class )
			&& class_exists( Occurrences_Entity::class );
	}
}
