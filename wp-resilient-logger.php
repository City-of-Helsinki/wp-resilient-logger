<?php

/**
 * Plugin Name: Helfi Resilient Logger
 * Description: WordPress-compatible implementation of city-of-helsinki/php-resilient-logger
 * Requires at least: 6.0.0
 * Requires PHP: 8.2
 * Version: 1.0.0
 * Author: City of Helsinki
 * Author URI: https://www.hel.fi
 * License: MIT License
 * License URI: https://opensource.org/license/mit
 * Text Domain: wp-resilient-logger
 * Domain Path: /languages
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function helsinki_wp_resilient_logger_require_files(): void {
	$plugin_dir = \plugin_dir_path( __FILE__ );

	if ( file_exists( $plugin_dir . 'vendor/autoload.php' ) ) {
		require_once $plugin_dir . 'vendor/autoload.php';
	}

	require_once $plugin_dir . 'features/factories.php';
	require_once $plugin_dir . 'features/cli.php';
	require_once $plugin_dir . 'features/cron.php';
	require_once $plugin_dir . 'features/database.php';
	require_once $plugin_dir . 'features/logging.php';
	require_once $plugin_dir . 'features/wsal.php';
}

\add_action( 'plugins_loaded', function() {

	helsinki_wp_resilient_logger_require_files();

	$flags = get_object_vars(
		helsinki_wp_resilient_logger_environment()
	);

	foreach ( $flags as $flag => $enabled ) {
		if ( $enabled ) {
			\add_filter( 'helsinki_wp_resilient_logger_' . $flag, '__return_true' );
		}
	}

	\do_action( 'helsinki_wp_resilient_logger_loaded' );

}, 11 );

\add_action( 'init', function() {

	\load_plugin_textdomain(
		'wp-resilient-logger',
		false,
		dirname( \plugin_basename( __FILE__ ) ) . '/languages'
	);

	\do_action( 'helsinki_wp_resilient_logger_init' );

} );

\register_activation_hook( __FILE__, 'helsinki_wp_resilient_logger_activate' );
function helsinki_wp_resilient_logger_activate(): void {

	helsinki_wp_resilient_logger_require_files();

	\do_action(
		'helsinki_wp_resilient_logger_activate',
		helsinki_wp_resilient_logger_environment()
	);

}

\register_deactivation_hook( __FILE__, 'helsinki_wp_resilient_logger_deactivate' );
function helsinki_wp_resilient_logger_deactivate(): void {

	\do_action( 'helsinki_wp_resilient_logger_deactivate' );

}

// \register_uninstall_hook( __FILE__, 'helsinki_wp_resilient_logger_uninstall' );
function helsinki_wp_resilient_logger_uninstall(): void {

	\do_action( 'helsinki_wp_resilient_logger_uninstall' );

}
