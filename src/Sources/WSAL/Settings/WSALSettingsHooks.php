<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Settings;

final class WSALSettingsHooks
{
	public function disable_editing( bool $result, mixed $user, string $action ): bool
	{
		return 'edit' === $action ? false : $result;
	}

	public function disable_settings_view( array $views ): array
	{
		return array_merge( $views, array( 'WSAL_Views_Settings' ) );
	}

	public function settings_notice(): void
	{
		global $current_screen;

		if ( 'wp-activity-log_page_wsal-settings' === $current_screen?->base ) {
			printf(
				'<div class="notice notice-warning">
					<p>%s</p>
				</div>',
				\esc_html( __( 'Settings marked with a asterisk (*) use predefined value from Helfi Resilient Logger plugin and cannot be changed by the site administrator.', 'wp-resilient-logger' ) )
			);
		}
	}

	public function forced_setting_text( string $translation, string $text ): string
	{
		if ( WSALForcedSettings::is_forced_setting_text( $text ) ) {
			$translation .= ' (*)';
		}

		return $translation;
	}

	public function overrides(): \Generator
	{
		foreach( WSALForcedSettings::cases() as $forced ) {
			yield "pre_option_{$forced->value}" => $forced->override();
		}

		yield $this->alerts_hook() => $this->alerts_override();
	}

	private function alerts_hook(): string
	{
		return 'pre_option_wsal_disabled-alerts';
	}

	private function alerts_override(): array
	{
		return WSALDisabledAlerts::codes();
	}
}
