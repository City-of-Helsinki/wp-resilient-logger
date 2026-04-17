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

\add_action( 'plugins_loaded', function() {

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

	$environment = helsinki_wp_resilient_logger_environment();

	if ( $environment->is_debug ) {
		\add_filter( 'helsinki_wp_resilient_logger_is_debug', '__return_true' );
	}

	if ( $environment->is_cli ) {
		\add_filter( 'helsinki_wp_resilient_logger_is_cli', '__return_true' );
	}

	if ( $environment->use_wp_cron ) {
		\add_filter( 'helsinki_wp_resilient_logger_use_wp_cron', '__return_true' );
	}

	if ( $environment->wsal_active ) {
		\add_filter( 'helsinki_wp_resilient_logger_wsal_active', '__return_true' );
	}

	if ( $environment->wsal_enforce_settings ) {
		\add_filter( 'helsinki_wp_resilient_logger_wsal_enforce_settings', '__return_true' );
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
