<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger;

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

	public function bool_setting( string $name ): bool
	{
		return isset( $this->settings[$name] ) ? (bool) $this->settings[$name] : false;
	}

	private function env_settings(): array
	{
		if ( ! defined( 'RESILIENT_LOGGER_SETTINGS' ) ) {
			throw new ResilientLoggerException::settings_missing();
		}

		$settings = constant( 'RESILIENT_LOGGER_SETTINGS' );
		if ( ! is_array( $settings ) ) {
			throw new ResilientLoggerException::settings_must_be_array();
		}

		return $settings;
	}

	private function determine_environment(): string
	{
		return \wp_get_environment_type();
	}
}
