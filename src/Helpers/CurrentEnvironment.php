<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Helpers;

use WpSecurityAuditLog;
use WSAL\Controllers\Alert_Manager;

readonly class CurrentEnvironment
{
	public bool $is_debug;
	public bool $is_cli;
	public bool $use_wp_cron;
	public bool $wsal_active;
	public bool $wsal_enforce_settings;

	public function __construct()
	{
		$this->is_debug = $this->determine( 'WP_DEBUG' );
		$this->is_cli = $this->determine( 'WP_CLI' );
		$this->use_wp_cron = $this->determine( 'RESILIENT_LOGGER_USE_WP_CRON' );
		$this->wsal_enforce_settings = $this->determine( 'RESILIENT_LOGGER_WSAL_ENFORCE_SETTINGS' );

		$this->wsal_active = class_exists( WpSecurityAuditLog::class )
			&& class_exists( Alert_Manager::class );
	}

	private function determine( string $name ): bool
	{
		return defined( $name ) && constant( $name );
	}
}
