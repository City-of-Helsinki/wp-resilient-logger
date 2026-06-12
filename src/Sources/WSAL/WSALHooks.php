<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use CityOfHelsinki\WP\ResilientLogger\Helpers\PostContent;
use CityOfHelsinki\WP\ResilientLogger\Sources\Augmentations\DataAugmentation;
use CityOfHelsinki\WP\ResilientLogger\Sources\Gates\EventGate;

final class WSALHooks
{
	private bool $is_premium;

	public function __construct(
		private PostContent $content,
		private DataAugmentation $augmentation,
		private EventGate $gate
	) {
		$this->is_premium = function_exists( 'wsal_freemius' )
			&& wsal_freemius()?->is_premium();
	}

	public function disable_ajax_actions(): void
	{
		\wp_send_json_error(
			\esc_html( _x( 'You are not allowed to reset or purge Activity Log data.', 'WSAL disabled ajax message', 'wp-resilient-logger' ) )
		);
	}

	public function disable_bulk_actions(): array
	{
		return array();
	}

	public function disable_footer_ad(): void
	{
		\remove_action( 'in_admin_footer', array( 'WSAL_AbstractView', 'in_admin_footer' ) );
	}

	public function disable_menu_ad(): void
	{
		\remove_submenu_page( 'wsal-auditlog', 'upgrade' );
	}

	public function skipped_views( array $views ): array
	{
		$skip = array( 'WSAL_Views_Help' );

		if ( ! $this->is_premium ) {
			$skip[] = '\WSAL\Views\Premium_Features';
		}

		return array_merge( $views, $skip );
	}

	public function disable_events_view( array $views ): array
	{
		return array_merge( $views, array( 'WSAL_Views_ToggleAlerts' ) );
	}

	public function events_notice(): void
	{
		global $current_screen;

		if ( 'wp-activity-log_page_wsal-togglealerts' === $current_screen?->base ) {
			printf(
				'<div class="notice notice-warning">
					<p>%s</p>
				</div>',
				\esc_html( __( 'When using Helsinki Resilient Logger plugin all Activity Log events are logged by default and any changes made to the events list are ignored.', 'wp-resilient-logger' ) )
			);
		}
	}

	public function capture_old_post_content( int $post_id ): void
	{
		$this->content->capture( $post_id );
	}

	public function prepare_event_data( array $data, int $event_id ): array
	{
		if ( ! $this->gate->should_log( $event_id, $data ) ) {
			return array();
		}

		$this->augmentation->augment( $data );

		return $data;
	}
}
