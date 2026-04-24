<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger;

use ResilientLogger\ResilientLogger;
use CityOfHelsinki\WP\ResilientLogger\Helpers\InternalLogger;
use CityOfHelsinki\WP\ResilientLogger\Helpers\ResilientLoggerException;

final class ResilientLoggerAdapter
{
	private ResilientLogger $logger;

	public function __construct(
		private ResilientLoggerConfig $config,
	) {
		$this->logger = ResilientLogger::create( $this->config->settings() );
		$this->logger::setInternalLogger(new InternalLogger());
	}

	public function submit_unsent_entries(): void
	{
		if ( $this->config->submit_unsent_entries() ) {
			$results = $this->logger->submitUnsentEntries();
			$failures = array_filter( $results, fn($success) => !$success );

			if ( $failures ) {
				throw ResilientLoggerException::submit_unset_entries_failed( $failures );
			}
		}
	}

	public function clear_sent_entries(): void
	{
		if ( $this->config->clear_sent_entries() ) {
			$this->logger->clearSentEntries();
		} else {
			throw ResilientLoggerException::clear_sent_entries_disabled();
		}
	}

	public function log_unknown_alert_id(int $id, string $source): void
	{
		$this->logger::getInternalLogger()
			->warning(
				sprintf( 'Resilient log unknown alert ID: %d', $id ),
				array('source' => $source)
			);
	}

	public function log_does_not_clear_sent_entries(string $source): void
	{
		$this->logger::getInternalLogger()
             ->info( sprintf(
				 '%s does not support clearing old entries.',
	             \esc_html( $source )
             ) );
	}

	public function log_does_create_entries(string $source): void
	{
		$this->logger::getInternalLogger()
	         ->info( sprintf(
	             '%s does not support direct instance creation.',
	             \esc_html( $source )
	         ) );
	}
}
