<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Helpers;

final class PostContent
{
	private array $contents;

	public function __construct()
	{
		$this->contents = array();
	}

	public function capture( int $post_id ): void
	{
		$content = $this->current( $post_id );

		if ( $content ) {
			$this->contents[$post_id] = $content;
		}
	}

	public function was_captured( int $post_id ): bool
	{
		return isset( $this->contents[$post_id] );
	}

	public function captured( int $post_id ): string
	{
		return $this->contents[$post_id] ?? '';
	}

	public function forget( int $post_id ): void
	{
		unset( $this->contents[$post_id] );
	}

	public function current( int $post_id ): string
	{
		$post = \get_post( $post_id );

		return $post instanceof \WP_Post && ! empty( $post->post_content )
			? $post->post_content
			: '';
	}
}
