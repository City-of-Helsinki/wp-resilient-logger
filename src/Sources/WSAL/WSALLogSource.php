<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Sources\AbstractLogSource;

final class WSALLogSource implements AbstractLogSource
{
	public function __construct(
		private WSALData $data,
		private WSALAlertAdapter $alerts,
		private ResilientLoggerConfig $config
	) {}

	public function create(int $level, mixed $message, array $context = []): ?AbstractLogSourceEntry
	{
		\do_action(
			'helsinki_wp_resilient_logger_does_not_create_entries',
			get_class( $this )
		);

		return null;
	}

	public function getUnsentEntries(int $chunkSize): \Generator
	{
		foreach( $this->data->unsent($chunkSize) as $row ) {
			yield $this->createEntry($row);
		}
	}

	public function clearSentEntries(int $daysToKeep): void
	{
		\do_action(
			'helsinki_wp_resilient_logger_does_not_clear_sent_entries',
			get_class( $this )
		);
	}

	private function createEntry(array $row): AbstractLogSourceEntry
	{
		return new WSALLogSourceEntry(
			$this->data,
			$this->alerts,
			$this->config,
			$row
		);
	}
}
