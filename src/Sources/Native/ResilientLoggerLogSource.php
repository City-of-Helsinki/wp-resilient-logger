<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Native;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Sources\AbstractLogSource;

final class ResilientLoggerLogSource implements AbstractLogSource
{
	public function __construct(
		private ResilientLoggerData $data,
		private ResilientLoggerConfig $config
	) {}

	public function create(int $level, mixed $message, array $context = []): ?AbstractLogSourceEntry
	{
		return $this->createEntry(
			$this->data->insert($level, $message, json_encode($context))
		);
	}

	public function getUnsentEntries(int $chunkSize): \Generator
	{
		foreach( $this->data->unsent($chunkSize) as $row ) {
			yield $this->createEntry($row);
		}
	}

	public function clearSentEntries(int $daysToKeep): void
	{
		$this->data->clear_sent($daysToKeep);
	}

	private function createEntry(array $row): AbstractLogSourceEntry
	{
		return new ResilientLoggerSourceEntry($this->data, $this->config, $row);
	}
}
