<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use WSAL\Entities\Occurrences_Entity;
use wpdb;

final class WSALData
{
	private readonly string $occurences_table;

	public function __construct(
		private readonly ResilientLoggerConfig $config,
		private readonly wpdb $db,
		private readonly string $sync_table,
		private readonly string $date_format
	) {
		$this->occurences_table = Occurrences_Entity::get_table_name($this->db);
	}

	public function unsent(int $limit): array
	{
		$ids = $this->unsent_ids( $limit );
		if ( $ids ) {
			$placeholders = array_fill( 0, count( $ids ), '%d' );

			$rows = Occurrences_Entity::load_array(
				sprintf(
					"id IN (%s) ORDER BY id ASC",
					implode( ',', $placeholders )
				),
				$ids,
			);
		} else {
			$rows = Occurrences_Entity::load_array(
	            '1 = 1 ORDER BY id ASC LIMIT %d',
	            array( $limit )
	        );
		}

		return $rows ? Occurrences_Entity::get_multi_meta_array($rows) : array();
	}

	private function unsent_ids( int $limit ): array
	{
		$sql = $this->db->prepare(
			"SELECT id FROM {$this->occurences_table} as ot
			LEFT JOIN {$this->sync_table} AS st
				ON st.occurrence_id = ot.id
			WHERE st.is_sent = 0
				OR st.occurrence_id IS NULL
			ORDER BY ot.id
			ASC LIMIT %d",
			$limit > 0 ? $limit : $this->config->chunk_size(),
		);

		return array_map( 'intval', $this->db->get_col( $sql ) );
	}

	public function mark_sent(int $occurrence_id): bool
	{
		if ( $occurrence_id > 0 ) {
			return (bool) $this->db->replace(
				$this->sync_table,
				array(
					'occurrence_id' => $occurrence_id,
					'is_sent' => 1,
					'sent_at' => \current_datetime()->format($this->date_format),
				),
				array('%d', '%d', '%s')
			);
		}

		return false;
	}
}
