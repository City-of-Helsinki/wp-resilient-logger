<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Settings;

final class WSALSettingsHooks
{
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
