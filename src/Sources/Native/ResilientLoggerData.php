<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Native;

use CityOfHelsinki\WP\ResilientLogger\Helpers\ResilientLoggerException;
use wpdb;

final class ResilientLoggerData
{
	private \wpdb $db;
	private string $table;
	private string $date_format;

	public function __construct(wpdb $db)
	{
		$this->db = $db;
		$this->table = $this->db->prefix . 'helfi_resilient_log';
		$this->date_format = 'Y-m-d H:i:s';
	}

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
			$limit > 0 ? $limit : 50
		);

		return $this->db->get_results( $sql,ARRAY_A ) ?: array();
	}

	public function mark_sent(int ...$ids): bool
	{
		$ids = array_filter( $ids );
		if ( ! $ids ) {
			return false;
		}

		if ( count( $ids ) > 1 ) {
			$placeholders = array_fill( 0, count( $ids ), '%d' );

			$sql = $this->db->prepare(
				sprintf(
					"UPDATE {$this->table} SET `in_sent` = 1 WHERE `id` IN (%s)",
					implode( ',', $placeholders )
				),
				...$ids
			);
		} else {
			$sql = $this->db->prepare(
				"UPDATE * FROM {$this->table} SET `in_sent` = 1 WHERE `id` = %d",
				reset( $ids )
			);
		}

		return (bool) $this->db->query( $sql );
	}

	public function clear_sent(int $days_to_keep): void
	{
		if ( $days_to_keep < 0 ) {
			throw ResilientLoggerException::invalid_days_to_keep();
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
