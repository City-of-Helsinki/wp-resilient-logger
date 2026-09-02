<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Augmentations;

use CityOfHelsinki\WP\ResilientLogger\Helpers\PostContent;
use ResilientLogger\Utils\HumanReadableDiffer;

final class AddContentDiff implements DataAugmentation
{
	public function __construct(
		private HumanReadableDiffer $differ,
		private PostContent $content
	) {}

	public function augment( array &$data ): void
	{
		$post_id = ! empty( $data['PostID'] ) ? (int) $data['PostID'] : null;

		if ( $post_id && $this->content->was_captured( $post_id ) ) {
			$data['ContentDiff'] = $this->generate_diff(
				$this->content->captured( $post_id ),
				$this->content->current( $post_id )
			);

			$this->content->forget( $post_id );
		}
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
