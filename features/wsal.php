<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

\add_action( 'helsinki_wp_resilient_logger_loaded', function() {

	if ( \apply_filters( 'helsinki_wp_resilient_logger_wsal_active', false ) ) {

		$settings = helsinki_wp_resilient_logger_wsal_settings_hooks();

		if ( \apply_filters( 'helsinki_wp_resilient_logger_wsal_disallow_edit_settings', false ) ) {
			\add_filter(
				'wsal_user_can',
				array( $settings, 'disable_editing' ),
				PHP_INT_MAX, 3
			);

			\add_filter(
				'wsal_skip_views',
				array( $settings, 'disable_settings_view' )
			);
		} else {
			\add_filter(
				'in_admin_header',
				array( $settings, 'settings_notice' ),
				100
			);

			\add_filter(
				'gettext_wp-security-audit-log',
				array( $settings, 'forced_setting_text' ),
				10, 2
			);
		}

		foreach( $settings->overrides() as $pre_option => $override ) {
			\add_filter( $pre_option, fn() => $override, PHP_INT_MAX, 1 );
		}

		$hooks = helsinki_wp_resilient_logger_wsal_hooks();

		\add_filter(
			'admin_menu',
			array( $hooks, 'disable_menu_ad' ),
			999
		);

		\add_filter(
			'in_admin_footer',
			array( $hooks, 'disable_footer_ad' ),
			-1
		);

		\add_filter(
			'wsal_skip_views',
			array( $hooks, 'skipped_views' )
		);

		if ( \apply_filters( 'helsinki_wp_resilient_logger_wsal_disable_events_view', false ) ) {
			\add_filter(
				'wsal_skip_views',
				array( $hooks, 'disable_events_view' )
			);
		} else {
			\add_filter(
				'in_admin_header',
				array( $hooks, 'events_notice' ),
				100
			);
		}

		\add_filter(
			'bulk_actions-toplevel_page_wsal-auditlog',
			array( $hooks, 'disable_bulk_actions' )
		);

		\add_action(
			'pre_post_update',
			array( $hooks, 'capture_old_post_content' )
		);

		\add_filter(
			'wsal_event_data_before_log',
			array( $hooks, 'augment_event_data' ),
			PHP_INT_MAX
		);

		\add_action(
			'wp_ajax_wsal_reset_settings',
			array( $hooks, 'disable_ajax_actions' ),
			1
		);

		\add_action(
			'wp_ajax_wsal_purge_activity',
			array( $hooks, 'disable_ajax_actions' ),
			1
		);
	}
}, 30 );
