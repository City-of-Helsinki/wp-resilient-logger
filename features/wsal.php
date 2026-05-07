<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

			\add_filter(
				'bulk_actions-toplevel_page_wsal-auditlog',
				'__return_empty_array'
			);

			\add_filter( 'wsal_skip_views', function( array $views ): array {
				return array_merge( $views, array(
					'WSAL_Views_Help',
					'WSAL_Views_Settings',
					'WSAL_Views_ToggleAlerts',
					'\WSAL\Views\Premium_Features',
				) );
			} );

			$settings = helsinki_wp_resilient_logger_wsal_settings_hooks();

			foreach( $settings->overrides() as $hook => $override ) {
				\add_filter( $hook, fn( $option ) => $override, PHP_INT_MAX, 1 );
			}
		}

		\add_action( 'admin_menu', function(): void {
			\remove_submenu_page( 'wsal-auditlog', 'upgrade' );
		}, 999 );

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
