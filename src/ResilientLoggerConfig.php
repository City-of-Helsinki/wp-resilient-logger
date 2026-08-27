<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger;

use CityOfHelsinki\WP\ResilientLogger\Helpers\ResilientLoggerException;

final class ResilientLoggerConfig
{
	private array $settings;

	public function __construct()
	{
		$this->settings = array_merge(
			$this->env_settings(),
			array( 'environment' => $this->determine_environment() )
		);
	}

	public function settings(): array
	{
		return $this->settings;
	}

	public function environment(): string
	{
		return $this->string_setting( __FUNCTION__, 'unknown' );
	}

	public function origin(): string
	{
		return $this->string_setting( __FUNCTION__, 'unknown' );
	}

	public function store_old_entries_days(): int
	{
		return $this->int_setting( __FUNCTION__, 30 );
	}

	public function batch_limit(): int
	{
		return $this->int_setting( __FUNCTION__, 5000 );
	}

	public function chunk_size(): int
	{
		return $this->int_setting( __FUNCTION__, 500 );
	}

	public function clear_sent_entries(): bool
	{
		return $this->bool_setting( __FUNCTION__ );
	}

	public function submit_unsent_entries(): bool
	{
		return $this->bool_setting( __FUNCTION__ );
	}

	private function string_setting( string $name, string $default = '' ): string
	{
		return $this->settings[ $name ] ?? $default;
	}

	private function int_setting( string $name, int $default ): int
	{
		return isset( $this->settings[$name] ) ? (int) $this->settings[$name] : $default;
	}

	private function bool_setting( string $name ): bool
	{
		return isset( $this->settings[ $name ] ) && (bool) $this->settings[ $name ];
	}

	private function env_settings(): array
	{
		if ( ! defined( 'RESILIENT_LOGGER_SETTINGS' ) ) {
			throw ResilientLoggerException::settings_missing();
		}

		$settings = constant( 'RESILIENT_LOGGER_SETTINGS' );
		if ( ! is_array( $settings ) ) {
			throw ResilientLoggerException::settings_must_be_array();
		}

		return $settings;
	}

	private function determine_environment(): string
	{
		return \wp_get_environment_type();
	}
}
