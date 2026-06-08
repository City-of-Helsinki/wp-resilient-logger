<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use CityOfHelsinki\WP\ResilientLogger\Cron\ResilientLoggerSchedule;

\add_action( 'helsinki_wp_resilient_logger_activate', function() {

	helsinki_wp_resilient_logger_setup_schedules();

} );

\add_action( 'helsinki_wp_resilient_logger_deactivate', function() {

	helsinki_wp_resilient_logger_clear_schedules();

} );

\add_action( 'helsinki_wp_resilient_logger_loaded', function() {

	if ( \apply_filters( 'helsinki_wp_resilient_logger_use_wp_cron', false ) ) {
		$scheduler = helsinki_wp_resilient_logger_scheduler();

		foreach ( $scheduler->handlers() as $action => $handler ) {
			\add_action( $action, $handler );
		}
	}

	\add_action( 'current_screen', function( WP_Screen $screen ) {
		if ( 'dashboard' === $screen->base ) {
			helsinki_wp_resilient_logger_ensure_schedules_exist();
		}
	} );

	\add_action( 'init', function() {
		if ( \apply_filters( 'helsinki_wp_resilient_logger_use_wp_cron', false ) ) {
			\add_filter( 'cron_schedules', 'helsinki_wp_resilient_logger_schedules' );
		}
	} );

}, 20 );

function helsinki_wp_resilient_logger_ensure_schedules_exist(): void {
	$transient = 'helsinki_wp_resilient_logger_schedules_exist';

	if ( ! \get_transient( $transient ) ) {
		helsinki_wp_resilient_logger_setup_schedules();

		\set_transient( $transient, 1, DAY_IN_SECONDS );
	}
}

function helsinki_wp_resilient_logger_setup_schedules(): void {
	if ( \apply_filters( 'helsinki_wp_resilient_logger_use_wp_cron', false ) ) {
		$scheduler = helsinki_wp_resilient_logger_scheduler();

		foreach ( $scheduler->schedules() as $action => $interval ) {
			if ( ! \wp_next_scheduled( $action ) ) {
                \wp_schedule_event( time(), $interval, $action );
            }
		}
	}
}

function helsinki_wp_resilient_logger_clear_schedules(): void {
	$scheduler = helsinki_wp_resilient_logger_scheduler();

	foreach ( $scheduler->schedules() as $action ) {
		\wp_clear_scheduled_hook( $action );
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
