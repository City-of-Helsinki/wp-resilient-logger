<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Native;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Utils\Helpers;

final class ResilientLoggerSourceEntry implements AbstractLogSourceEntry
{
	private int $id;
	private array $row;
	private bool $is_sent;

	public function __construct(
		private ResilientLoggerData $data,
		private ResilientLoggerConfig $config,
		array $row
	) {
		$this->id = (int) $row['id'];
		unset( $row['id'] );

		$this->is_sent = (bool) $row['is_sent'];
		unset( $row['is_sent'] );

		$this->row = $row;
	}

	public function getId(): int|string
	{
		return $this->id;
	}

	public function getDocument(): array
	{
		$message   = json_decode($this->row['message'], true);
		$context   = json_decode($this->row['context'], true) ?: [];
		$createdAt = new \DateTimeImmutable($this->row['created_at']);
		$message   = is_array($message) ? json_encode($message) : (string) $message;

		$actor     = $context['actor']     ?? 'unknown';
		$operation = $context['operation'] ?? 'MANUAL';
		$target    = $context['target']    ?? 'unknown';

		unset($context['actor'], $context['operation'], $context['target']);

		return array(
			'@timestamp' => $createdAt,
			'audit_event' => array(
				'actor'       => Helpers::valueAsArray($actor),
				'date_time'   => $createdAt,
				'operation'   => $operation,
				'origin'      => $this->config->origin(),
				'target'      => Helpers::valueAsArray($target),
				'environment' => $this->config->environment(),
				'message'     => $message,
				'level'       => (int) $this->row['level'],
				'extra'       => $context,
			),
		);
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
