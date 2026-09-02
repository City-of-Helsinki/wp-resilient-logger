<?php

namespace CityOfHelsinki\WP\ResilientLogger\Database\Actions;

use CityOfHelsinki\WP\ResilientLogger\Helpers\ResilientLoggerException;
use CityOfHelsinki\WP\ResilientLogger\Database\ResilientLoggerTables;
use wpdb;

final class ResetDatabase
{
	private ?ResilientLoggerException $exception;

	public function __construct(
		private wpdb $db
	) {}

	public function execute(): void
	{
		$this->exception = null;

		foreach ( ResilientLoggerTables::list( $this->db ) as $table ) {
			if ( ! $this->db->query( "TRUNCATE TABLE {$table}" ) ) {
				$this->exception = ResilientLoggerException::db_reset_failed(
					$table,
					$this->exception
				);
			}
		}

		if ( $this->exception ) {
			throw ResilientLoggerException::db_reset_failed(
				'Default',
				$this->exception
			);
		}
	}
}
