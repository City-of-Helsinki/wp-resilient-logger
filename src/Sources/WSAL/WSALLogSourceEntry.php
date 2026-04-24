<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Utils\Helpers;

final class WSALLogSourceEntry implements AbstractLogSourceEntry
{
	private int $id;
	private array $row;
	private array $details;
	private array $target;
	private bool $is_sent;

	public function __construct(
		private WSALData $data,
		WSALAlertAdapter $alerts,
		private ResilientLoggerConfig $config,
		array $row
	) {
		$this->id = (int) $row['id'];
		unset( $row['id'] );

		$this->is_sent = (bool) $row['is_sent'];
		unset( $row['is_sent'] );

		$this->row = $row;
		$this->details = $alerts->parseAlertDetails(
			(int) $this->row['alert_id']
		);
		$this->target = $alerts->parseTarget(
			$this->row['alert_id'] ?? 'none',
			$this->row['site_id'] ?? '1',
			$this->parseMetadata()
		);
	}

	public function getId(): int|string
	{
		return $this->id;
	}

	public function getDocument(): array
	{
		$meta      = $this->parseMetadata();
		$timestamp = $this->parseDateString((int) $this->row['created_on']);

		return [
			'@timestamp' => $timestamp,
			'audit_event' => [
				'actor' => [
					'user_id' => (string) ($meta['CurrentID'] ?? '0'),
					'ip'      => (string) ($meta['ClientIP'] ?? 'unknown'),
				],
				'date_time'   => $timestamp,
				'operation'   => $this->details['operation'],
				'origin'      => $this->config->origin(),
				'target'      => $this->target,
				'environment' => $this->config->environment(),
				'message'     => (string) ($this->row['message'] ?? ''),
				'level'       => 200,
				'extra'       => array_merge($meta, [
					'WSAL_AlertId'   => (int) $this->row['alert_id'],
					'WSAL_AlertDesc' => $this->details['description'],
				]),
			],
		];
	}

	private function parseMetadata(): array
	{
		return $this->row['meta_values'] ?? [];
	}

	private function parseDateString(int $unixTime): string
	{
		$timestamp = new \DateTimeImmutable("@{$unixTime}");
		return $timestamp->format(\DateTime::ATOM);
	}

	public function isSent(): bool
	{
		return $this->is_sent;
	}

	public function markSent(): void
	{
		if ( ! $this->isSent() ) {
			$this->is_sent = $this->data->mark_sent( $this->getId() );
		}
	}
}
