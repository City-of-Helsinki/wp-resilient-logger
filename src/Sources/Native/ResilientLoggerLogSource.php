<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Native;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Utils\Helpers;

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
		$message = json_decode( $this->row['message'], true ) ?: '';
		$message = is_array( $message ) ? json_encode( $message ) : (string) $message;

		$context = json_decode($this->row['context'], true) ?: array();
		$actor     = $context['actor']     ?? 'unknown';
		$operation = $context['operation'] ?? 'MANUAL';
		$target    = $context['target']    ?? 'unknown';

		unset( $context['actor'], $context['operation'], $context['target'] );

		$entry = array(
			'id' => isset( $row['id'] ) ? (int) $row['id'] : 0,
			'is_sent' => isset( $row['is_sent'] ) ? (bool) $row['is_sent'] : false,
			'message' => $message,
			'context' => $context,
			'created_at' => new \DateTimeImmutable( $row['created_at'] ),
			'level' => (int) $row['level'],
			'actor' => Helpers::valueAsArray( $actor ),
			'operation' => $operation,
			'target' => Helpers::valueAsArray( $target ),
			'origin' => $this->config->origin(),
			'environment' => $this->config->environment(),
		);

		return new ResilientLoggerSourceEntry( (object) $entry, $this->data );
	}
}
