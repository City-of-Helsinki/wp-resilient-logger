<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Helpers;

use WpSecurityAuditLog;
use WSAL\Controllers\Alert_Manager;
use CityOfHelsinki\WP\ResilientLogger\ResilientLogger;
use ResilientLogger\Utils\HumanReadableDiffer;

class WSALAugment
{
	/** Stores previous post contents here for diff as array [postId => postContents] */
	private array $post_contents;

	public function __construct(
		private HumanReadableDiffer $differ
	) {
		$this->post_contents = array();
	}

	public function capture_old_post_content( int $post_id ): void
	{
		$content = $this->get_post_content( $post_id );

		if ( $content ) {
			$this->post_contents[$post_id] = $content;
		}
	}

	public function augment_event_data( array $data ): void
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
