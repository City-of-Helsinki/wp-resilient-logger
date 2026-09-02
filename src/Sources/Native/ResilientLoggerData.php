<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Native;

use CityOfHelsinki\WP\ResilientLogger\Database\ResilientLoggerTables;
use CityOfHelsinki\WP\ResilientLogger\Helpers\ResilientLoggerException;
use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use wpdb;

final class ResilientLoggerData
{
	public function __construct(
		private readonly ResilientLoggerConfig $config,
		private readonly wpdb $db,
		private readonly string $table,
		private readonly string $date_format
	) {}

	public function insert(int $level, string $message, string $context): array
	{
		$inserted = $this->db->insert(
			$this->table,
			[
				'level'      => $level,
				'message'    => $message,
				'context'    => $context,
				'created_at' => \current_datetime()->format($this->date_format),
				'is_sent'    => 0,
			],
			['%d', '%s', '%s', '%s', '%d']
		);

		if ( false === $inserted ) {
			throw ResilientLoggerException::log_insert_failed();
		}

		return $this->find_by_id($this->db->insert_id);
	}

	public function find_by_id(int $id): array
	{
		if ( ! $id ) {
			throw ResilientLoggerException::invalid_log_entry( $id );
		}

		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table} WHERE %d",
			$id
		);

		$row = $this->db->get_row( $sql );
		if ( empty( $row ) ) {
			throw ResilientLoggerException::invalid_log_entry( $id );
		}

		return $row;
	}

	public function unsent(int $limit): array
	{
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table} WHERE is_sent = 0 ORDER BY created_at ASC LIMIT %d",
			$limit > 0 ? $limit : $this->config->chunk_size()
		);

		return $this->db->get_results( $sql,ARRAY_A ) ?: array();
	}

	public function mark_sent(int $id): bool
	{
		return false !== $this->db->update(
			$this->table,
			['is_sent' => 1],
			['id' => $id],
			['%d'],
			['%d']
		);
	}

	public function clear_sent(int $days_to_keep): void
	{
		if ( $days_to_keep < 0 ) {
			$days_to_keep = $this->config->store_old_entries_days();
		}

		$sql = $this->db->prepare(
			"DELETE FROM {$this->table} WHERE is_sent = 1 AND created_at <= %s",
			\current_datetime()
				->modify("-{$days_to_keep} days")
				->format($this->date_format)
		);

		$this->db->query( $sql );
	}
}
