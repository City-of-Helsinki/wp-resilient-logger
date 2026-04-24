<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Helpers;

final class ResilientLoggerException extends \Exception
{
	public static function settings_missing(): self
	{
		return new self( '"RESILIENT_LOGGER_SETTINGS" is missing' );
	}

	public static function settings_must_be_array(): self
	{
		return new self( '"RESILIENT_LOGGER_SETTINGS" must be an array' );
	}

	public static function clear_sent_entries_disabled(): self
	{
		return new self( "ResilientLogger error: clear_sent_entries is disabled in config" );
	}

	public static function submit_unset_entries_failed( array $failures ): self
	{
		return new self( sprintf(
			"ResilientLogger warning: %d submissions failed. IDs: %s",
			count( $failures ),
			implode( ', ', array_keys( $failures ) )
		) );
	}

	public static function invalid_log_entry( int|string $id ): self
	{
		return new self( sprintf(
			"ResilientLogger error: invalid log entry \"%s\".",
			\esc_html( $id )
		) );
	}

	public static function log_insert_failed(): self
	{
		return new self( 'Failed to insert log into WordPress database.' );
	}

	public static function invalid_days_to_keep(): self
	{
		return new self( 'Days to keep must be zero or a positive integer.' );
	}
}
