<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Native;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use stdClass;

final class ResilientLoggerSourceEntry implements AbstractLogSourceEntry
{
	private int $id;
	private array $row;
	private bool $is_sent;

	public function __construct(
		private stdClass $entry,
		private ResilientLoggerData $data
	) {}

	public function getId(): int|string
	{
		return $this->entry->id;
	}

	public function getDocument(): array
	{
		return array(
			'@timestamp' => $this->entry->created_at,
			'audit_event' => array(
				'actor'       => $this->entry->actor,
				'date_time'   => $this->entry->created_at,
				'operation'   => $this->entry->operation,
				'origin'      => $this->entry->origin,
				'target'      => $this->entry->target,
				'environment' => $this->entry->environment,
				'message'     => $this->entry->message,
				'level'       => $this->entry->level,
				'extra'       => $this->entry->context,
			),
		);
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
