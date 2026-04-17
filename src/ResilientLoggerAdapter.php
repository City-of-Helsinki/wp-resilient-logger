<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger;

use ResilientLogger\ResilientLogger;
use WP\helfi_resilient_logger\Helpers\InternalLogger;

final class ResilientLoggerAdapter
{
	private ResilientLogger $logger;

	public function __construct(
		private ResilientLoggerConfig $config,
	) {
		$this->logger = ResilientLogger::create( $this->config->settings() );
	}

	public function wsal_enforce_settings(): bool
	{
		return $this->config->bool_setting( 'enforce_wsal_settings' );
	}

	public function submit_unsent_entries(): void
	{
		if ( $this->config->bool_setting( 'submit_unsent_entries' ) ) {
			$results = $this->logger->submitUnsentEntries();
			$failures = array_filter( $results, fn($success) => !$success );

			if ( $failures ) {
				throw new ResilientLoggerException::submit_unset_entries_failed( $failures );
			}
		}
	}

	public function clear_sent_entries(): void
	{
		if ( $this->config->bool_setting( 'clear_sent_entries' ) ) {
			$this->logger->clearSentEntries();
		} else {
			throw new ResilientLoggerException::clear_sent_entries_disabled();
		}
	}

	public function log_emergency( $message, array $context = array() ): void
	{

	}

	public function log_alert( $message, array $context = array() ): void
	{

	}

	public function log_critical( $message, array $context = array() ): void
	{

	}

	public function log_error( $message, array $context = array() ): void
	{

	}

	public function log_warning( $message, array $context = array() ): void
	{

	}

	public function log_notice( $message, array $context = array() ): void
	{

	}

	public function log_info( $message, array $context = array() ): void
	{

	}

	public function log_debug( $message, array $context = array() ): void
	{

	}
}
