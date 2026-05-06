<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use stdClass;

final class WSALLogSourceEntry implements AbstractLogSourceEntry
{
	public function __construct(
		private stdClass $alert,
		private WSALData $data
	) {}

	public function getId(): int|string
	{
		return $this->alert->id;
	}

	public function getDocument(): array
	{
		$timestamp = $this->parseDateString();

		return array(
			'@timestamp' => $timestamp,
			'audit_event' => array(
				'actor' => array(
					'user_id' => (string) ($this->alert->meta['CurrentID'] ?? '0'),
					'ip'      => (string) ($this->alert->meta['ClientIP'] ?? 'unknown'),
				),
				'date_time'   => $timestamp,
				'operation'   => $this->alert->details['operation'],
				'origin'      => $this->alert->origin,
				'target'      => $this->alert->target,
				'environment' => $this->alert->environment,
				'message'     => $this->alert->message,
				'level'       => 200,
				'extra'       => array_merge( $this->alert->meta, array(
					'WSAL_AlertId'   => $this->alert->alert_id,
					'WSAL_AlertDesc' => $this->alert->details['description'],
				) ),
			),
		);
	}

	private function parseDateString(): string
	{
		$timestamp = new \DateTimeImmutable("@{$this->alert->created_on}");

		return $timestamp->format(\DateTime::ATOM);
	}

	public function isSent(): bool
	{
		return $this->alert->is_sent;
	}

	public function markSent(): void
	{
		if ( ! $this->isSent() ) {
			$this->alert->is_sent = $this->data->mark_sent( $this->alert->id );
		}
	}
}
