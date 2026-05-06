<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Lookups\AlertDetails;
use CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Lookups\AlertTarget;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Sources\AbstractLogSource;

final class WSALLogSource implements AbstractLogSource
{
	public function __construct(
		private WSALData $data,
		private AlertDetails $details,
		private AlertTarget $target,
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
		$entry = array(
			'id' => isset( $row['id'] ) ? (int) $row['id'] : 0,
			'site_id' => $row['site_id'] ?? 1,
			'alert_id' => (int) $row['alert_id'] ?? 0,
			'is_sent' => isset( $row['is_sent'] ) ? (bool) $row['is_sent'] : false,
			'message' => (string) ($row['message'] ?? ''),
			'meta' => $row['meta_values'] ?? array(),
			'created_on' => (int) $row['created_on'] ?? time(),
			'origin' => $this->config->origin(),
			'environment' => $this->config->environment(),
		);

		$entry['details'] = $this->details->parse( $entry['alert_id'] );

		$entry['target'] = array_merge(
			array( 'site_id' => $entry['site_id'] ),
			$this->target->parse( $entry['meta'], $entry['alert_id'] )
		);

		return new WSALLogSourceEntry( (object) $entry, $this->data );
	}
}
