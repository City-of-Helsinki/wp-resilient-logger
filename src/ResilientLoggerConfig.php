<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger;

final class ResilientLoggerConfig
{
	private array $settings;

	public function __construct()
	{
		$settings = $this->env_settings();

		foreach ( $this->override_options() as $key => $option ) {
			$settings[$key] = $this->parse_option( $option, $settings[$key] ?? '' );
		}

		$settings['environment'] = $this->determine_environment();

		$this->settings = $settings;
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

	private function parse_option( string $key, mixed $fallback ): mixed
	{
		$override = \get_option( $key );

		if (
			$override !== false
			&& is_string( $override )
			&& trim( $override ) !== ''
		) {
			settype( $override, gettype( $fallback ) );

			return $override;
		}

		return $fallback;
    }

	private function override_options(): array
	{
		return array(
			'origin' => 'helfi_resilient_logger_origin',
		    'environment' => 'helfi_resilient_logger_environment',
		    'store_old_entries_days' => 'helfi_resilient_logger_store_old_entries_days',
		    'batch_limit' => 'helfi_resilient_logger_batch_limit',
		    'chunk_size' => 'helfi_resilient_logger_chunk_size',
		    'submit_unsent_entries' => 'helfi_resilient_logger_submit_unsent_entries',
		    'clear_sent_entries' => 'helfi_resilient_logger_clear_sent_entries',
		);
	}

	private function determine_environment(): string
	{
		return \wp_get_environment_type();
	}
}
