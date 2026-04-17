<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP\helfi_resilient_logger\Commands\ResilientLoggerEntriesCli;

\add_action( 'helsinki_wp_resilient_logger_loaded', function() {

	\add_action( 'cli_init', function() {
		if ( \apply_filters( 'helsinki_wp_resilient_logger_is_cli', false ) ) {
			\WP_CLI::add_command(
				'resilient-logger entries',
				ResilientLoggerEntriesCli::class
			);
		}
	} );

}, 20 );
