<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

\add_action( 'helsinki_wp_resilient_logger_activate', function() {

	helsinki_wp_resilient_logger_db_migrator()->create_tables();

} );

// \add_action( 'helsinki_wp_resilient_logger_uninstall', function() {
//
// 	// TODO: drop custom database tables
//
// } );

\add_action( 'helsinki_wp_resilient_logger_loaded', function() {

	if ( \is_multisite() ) {
		$migrator = helsinki_wp_resilient_logger_db_migrator();

		\add_action( 'wp_initialize_site', array( $migrator, 'setup_network_site' ) );
	}

}, 20 );
