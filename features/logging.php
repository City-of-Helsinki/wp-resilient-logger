<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


\add_action( 'helsinki_wp_resilient_logger_loaded', function() {

	$adapter = helsinki_wp_resilient_logger();

	\add_action(
		'helsinki_wp_resilient_logger_submit_unsent_entries',
		array( $adapter, 'submit_unsent_entries' )
	);

	\add_action(
		'helsinki_wp_resilient_logger_clear_sent_entries',
		array( $adapter, 'clear_sent_entries' )
	);

}, 10 );
