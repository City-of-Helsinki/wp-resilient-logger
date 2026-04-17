<?php
/**
 * Plugin Name: Helfi Resilient Logger
 */

declare(strict_types=1);

use WP\helfi_resilient_logger\Bootstrap;
use WP\helfi_resilient_logger\CurrentEnvironment;
use WP\helfi_resilient_logger\ResilientLoggerAdapter;
use WP\helfi_resilient_logger\ResilientLoggerConfig;
use WP\helfi_resilient_logger\ResilientLoggerScheduler; // TODO:
use WP\helfi_resilient_logger\ResilientLoggerCli; // TODO:
use WP\helfi_resilient_logger\Enums\ResilientLoggerSchedule;
use WP\helfi_resilient_logger\Enums\WSALForcedSettings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

function helsinki_wp_resilient_logger(): ResilientLoggerAdapter {
	return new ResilientLoggerAdapter(
		helsinki_wp_resilient_logger_config(),
	);
}

function helsinki_wp_resilient_logger_environment(): CurrentEnvironment {
	return new CurrentEnvironment();
}

function helsinki_wp_resilient_logger_config(): ResilientLoggerConfig {
	return new ResilientLoggerConfig();
}

function helsinki_wp_resilient_logger_scheduler(): ResilientLoggerScheduler {
	return new ResilientLoggerScheduler();
}

\add_action( 'plugins_loaded', 'helsinki_wp_resilient_logger_env_setup', 11 );
function helsinki_wp_resilient_logger_env_setup(): void {
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
}

\add_action( 'plugins_loaded', 'helsinki_wp_resilient_logger_loaded', 12 );
function helsinki_wp_resilient_logger_loaded(): void {
	\do_action( 'helsinki_wp_resilient_logger_load' );

	$adapter = helsinki_wp_resilient_logger();

	if ( $adapter->wsal_enforce_settings() ) {
		\add_filter(
			'helsinki_wp_resilient_logger_wsal_enforce_settings',
			'__return_true'
		);
	}

	$operations = array(
		'submit_unsent_entries',
		'clear_sent_entries',
	);

	foreach ( $operations as $operation ) {
		\add_action(
			'helsinki_wp_resilient_logger_' . $operation,
			array( $adapter, $operation )
		);
	}

	$log_actions = array(
		'log_emergency',
		'log_alert',
		'log_critical',
		'log_error',
		'log_warning',
		'log_notice',
		'log_info',
		'log_debug',
	);

	foreach ( $log_actions as $log_action ) {
		\add_action(
			'helsinki_wp_resilient_logger_' . $log_action,
			array( $adapter, $log_action ),
			10, 2
		);
	}

	\do_action( 'helsinki_wp_resilient_logger_loaded', $adapter );
}

\add_action( 'helsinki_wp_resilient_logger_loaded', 'helsinki_wp_resilient_logger_features', 10 );
function helsinki_wp_resilient_logger_features(): void {
	if ( \apply_filters( 'helsinki_wp_resilient_logger_use_wp_cron', false ) ) {
		$scheduler = helsinki_wp_resilient_logger_scheduler();

		foreach ( $scheduler->handlers() as $action => $handler ) {
			\add_action( $action, $handler );
		}
	}

	\add_action( 'cli_init', 'helsinki_wp_resilient_logger_cli' );
	\add_action( 'init', 'helsinki_wp_resilient_logger_init' );
}

\add_action( 'helsinki_wp_resilient_logger_loaded', 'helsinki_wp_resilient_logger_integrations', 20 );
function helsinki_wp_resilient_logger_integrations(): void {
	if ( \apply_filters( 'helsinki_wp_resilient_logger_wsal_active', false ) ) {

		if ( \apply_filters( 'helsinki_wp_resilient_logger_wsal_enforce_settings', false ) ) {
			\add_filter(
				'wsal_user_can',
				function( bool $result, mixed $user, string $action ): bool {
					return 'edit' === $action ? false : $result;
				},
				PHP_INT_MAX, 3
			);

			foreach( WSALForcedSettings::cases() as $forced ) {
				\add_filter(
					"pre_option_{$forced->value}",
					fn( $option ) => $forced->override(),
					PHP_INT_MAX, 1
				);
			}
		}
	}
}

function helsinki_wp_resilient_logger_cli(): void {
	if ( \apply_filters( 'helsinki_wp_resilient_logger_is_cli', false ) ) {
		\WP_CLI::add_command( 'resilient-logger', ResilientLoggerCli::class );
	}
}

function helsinki_wp_resilient_logger_init(): void {
	if ( \apply_filters( 'helsinki_wp_resilient_logger_use_wp_cron', false ) ) {
		\add_filter( 'cron_schedules', 'helsinki_wp_resilient_logger_schedules' );
	}
}

function helsinki_wp_resilient_logger_schedules( array $schedules ): array {
	return array_reduce(
		ResilientLoggerSchedule::cases(),
		function( array $schedules, ResilientLoggerSchedule $schedule ) {
			$schedules[$schedule->value] = array(
				'interval' => $schedule->interval(),
				'display'  => $schedule->label(),
			);

			return $schedules;
		},
		$schedules
	);
}

\register_activation_hook( __FILE__, 'helsinki_wp_resilient_logger_activate' );
function helsinki_wp_resilient_logger_activate(): void {
	$environment = helsinki_wp_resilient_logger_environment();

	\do_action( 'helsinki_wp_resilient_logger_activate', $environment );

	if ( $environment->use_wp_cron ) {
		$scheduler = helsinki_wp_resilient_logger_scheduler();

		foreach ( $scheduler->schedules() as $interval => $action ) {
			if ( ! \wp_next_scheduled( $action ) ) {
                \wp_schedule_event( time(), $interval, $action );
            }
		}
	}

	\do_action( 'helsinki_wp_resilient_logger_activated', $environment );
}

\register_deactivation_hook( __FILE__, 'helsinki_wp_resilient_logger_deactivate' );
function helsinki_wp_resilient_logger_deactivate(): void {
	\do_action( 'helsinki_wp_resilient_logger_deactivate' );

	$scheduler = helsinki_wp_resilient_logger_scheduler();

	foreach ( $scheduler->schedules() as $action ) {
		\wp_clear_scheduled_hook( $action );
	}

	\do_action( 'helsinki_wp_resilient_logger_deactivated' );
}

// \register_uninstall_hook( __FILE__, 'helsinki_wp_resilient_logger_uninstall' );
function helsinki_wp_resilient_logger_uninstall(): void {
	\do_action( 'helsinki_wp_resilient_logger_uninstall' );

	\do_action( 'helsinki_wp_resilient_logger_uninstalled' );
}
