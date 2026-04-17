<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger;

use ResilientLogger\ResilientLogger;
use CityOfHelsinki\WP\ResilientLogger\Helpers\InternalLogger;

final class ResilientLoggerAdapter
{
	private ResilientLogger $logger;

	public function __construct(
		private ResilientLoggerConfig $config,
	) {
		$this->logger = ResilientLogger::create( $this->config->settings() );
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
}
