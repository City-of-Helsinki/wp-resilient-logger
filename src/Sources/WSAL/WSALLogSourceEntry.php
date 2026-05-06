<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use stdClass;

final class WSALLogSourceEntry implements AbstractLogSourceEntry
{
	public function __construct(
		private stdClass $entry,
		private WSALData $data
	) {}

	public function getId(): int|string
	{
		return $this->entry->id;
	}

	public function getDocument(): array
	{
		$timestamp = $this->parseDateString();

		return array(
			'@timestamp' => $timestamp,
			'audit_event' => array(
				'actor' => array(
					'user_id' => (string) ($this->entry->meta['CurrentID'] ?? '0'),
					'ip'      => (string) ($this->entry->meta['ClientIP'] ?? 'unknown'),
				),
				'date_time'   => $timestamp,
				'operation'   => $this->entry->details['operation'],
				'origin'      => $this->entry->origin,
				'target'      => $this->entry->target,
				'environment' => $this->entry->environment,
				'message'     => $this->entry->message,
				'level'       => 200,
				'extra'       => array_merge( $this->entry->meta, array(
					'WSAL_AlertId'   => $this->entry->alert_id,
					'WSAL_AlertDesc' => $this->entry->details['description'],
				) ),
			),
		);
	}

	private function parseDateString(): string
	{
		$timestamp = new \DateTimeImmutable("@{$this->entry->created_on}");

		return $timestamp->format(\DateTime::ATOM);
	}

	public function isSent(): bool
	{
		return $this->entry->is_sent;
	}

	public function markSent(): void
	{
		if ( ! $this->isSent() ) {
			$this->entry->is_sent = $this->data->mark_sent( $this->entry->id );
		}
	}
}
