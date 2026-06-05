<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

use ResilientLogger\Utils\HumanReadableDiffer;

final class WSALHooks
{
	/** Stores previous post contents here for diff as array [postId => postContents] */
	private array $post_contents;
	private bool $is_premium;

	public function __construct(
		private HumanReadableDiffer $differ
	) {
		$this->post_contents = array();

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
		$content = $this->get_post_content( $post_id );

		if ( $content ) {
			$this->post_contents[$post_id] = $content;
		}
	}

	public function augment_event_data( array $data ): array
	{
		$this->add_content_diff( $data );
		$this->add_request_id( $data );

		return $data;
	}

	private function get_post_content( int $post_id ): string
	{
		$post = \get_post( $post_id );

		return $post instanceof \WP_Post && ! empty( $post->post_content )
			? $post->post_content
			: '';
	}

	private function add_content_diff( array &$data ): void
	{
		$post_id = ! empty( $data['PostID'] ) ? (int) $data['PostID'] : null;

		if ( $post_id && isset( $this->post_contents[$post_id] ) ) {
			$data['ContentDiff'] = $this->generate_diff(
				$this->post_contents[$post_id],
				$this->get_post_content( $post_id )
			);

			unset( $this->post_contents[$post_id] );
		}
	}

	private function add_request_id( array &$data ): void
	{
		$data['RequestID'] = $this->header_value( 'X-Request-ID', 'N/A' );
	}

	private function header_value( string $name, string $default ): string
	{
		// Normalize the name to the PHP $_SERVER format
        // Example: 'X-Request-ID' becomes 'HTTP_X_REQUEST_ID'
		$header = 'HTTP_' . strtoupper( str_replace( '-', '_', $name ) );

		return ! empty( $_SERVER[$header] )
			? \sanitize_text_field( $_SERVER[$header] )
			: $default;
	}

	private function generate_diff( string $old, string $new ): string
	{
		try {
			return $this->differ->diff( $old, $new );
		} catch ( \Exception $e ) {
			return "Error generating diff: " . $e->getMessage();
		}
	}
}
