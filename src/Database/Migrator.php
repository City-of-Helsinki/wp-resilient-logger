<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Database;

class Migrator
{
	public function create_tables(): void
	{
		if ( \is_multisite() ) {
			$this->create_tables_for_sites( $this->get_sites() );
		} else {
			$this->create_entity_tables();
		}
	}

	public function setup_network_site( \WP_Site $site ): void
	{
		\switch_to_blog( (int) $site->blog_id );

		try {
			$this->create_entity_tables();
		} finally {
			\restore_current_blog();
		}
	}

	private function create_tables_for_sites( array $site_ids ): void
	{
		$initial_site_id = \get_current_blog_id();
		$switched_stack = $GLOBALS[ '_wp_switched_stack' ];
		$is_switched = $GLOBALS[ 'switched' ];

		array_walk( $site_ids, function( $site_id ) {
			\switch_to_blog( (int) $site_id );
			$this->create_entity_tables();
		} );

		\switch_to_blog( $initial_site_id );

		$GLOBALS[ '_wp_switched_stack' ] = $switched_stack;
		$GLOBALS[ 'switched' ] = $is_switched;
	}

	private function get_sites(): array
	{
		return \get_sites( array(
			'fields' => 'ids',
			'number' => 0,
		) );
	}

	private function create_entity_tables(): void
	{
		ResilientLogEntity::install();

		if ( \apply_filters( 'helsinki_wp_resilient_logger_wsal_active', true ) ) {
			WSALSyncEntity::install();
		}
	}
}
