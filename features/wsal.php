<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\WSALForcedSettings;

\add_action( 'helsinki_wp_resilient_logger_loaded', function() {

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

		$hooks = helsinki_wp_resilient_logger_wsal_hooks();

		\add_action(
			'pre_post_update',
			array( $hooks, 'capture_old_post_content' )
		);

		\add_filter(
			'wsal_event_data_before_log',
			array( $hooks, 'augment_event_data' ),
			PHP_INT_MAX
		);
	}

}, 30 );
