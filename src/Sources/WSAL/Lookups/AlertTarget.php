<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Lookups;

final class AlertTarget
{
	private const TARGET_LOOKUP = array(
		// 1. Primary Entity Anchors (Highest Priority)
		'PostType'        => 'post', // always present in meta
		'PostID'          => 'post', // always present in meta
		'ID'              => 'post',            // Found in Tags/Categories metadata
		'TargetSessionID' => 'user_session',
		'SessionID'       => 'user_session', // always present in meta
		'TermID'          => 'taxonomy_term',
		'UserID'          => 'user',

		// 2. Structural Anchors (Required for Taxonomy events)
		'cat_link'        => 'taxonomy_term',
		'Slug'            => 'taxonomy_term',   // Critical: Used in 2121, 2123, 2052, etc.
		'slug'            => 'taxonomy_term',
		'CategoryName'    => 'taxonomy_term',
		'TagName'         => 'taxonomy_term',
		'TagLink'         => 'taxonomy_term',

		// 3. Asset Anchors
		'FileName'        => 'file',            // Found in 2010
		'FilePath'        => 'file',            // Found in 2010 (as Directory)

		// 4. Descriptor Fallbacks (Lowest Priority)
		'task_name'       => 'cron',
		'PostStatus'      => 'post',
		'TargetUserRole'  => 'user',
		// 'ClientIP'        => 'user',
		// 'IPAddress'       => 'user',
	);

	public function __construct(
		private array $plugins,
		private array $themes
	) {}

	public function parse( array $meta, string|int $id ): array
	{
		if ( $this->is_post( $meta ) ) {
			return array(
				'id' => $meta['PostID'],
				'type' => $meta['PostType'],
			);
		}

		$target_type = $meta['Object'] ?? 'unmapped_target';

		if ( $this->is_plugin( $meta, $target_type ) ) {
			return array(
				'id' => $this->plugin_name( $meta, $target_type ),
				'type' => 'plugin',
			);
		}

		if ( $this->is_theme( $meta, $target_type ) ) {
			return array(
				'id' => $this->theme_name( $meta, $target_type ),
				'type' => 'theme',
			);
		}

		$target_id = $meta['Object'] ?? 'unknown';
		foreach ( self::TARGET_LOOKUP as $key => $lookup_type ) {
			if ( ! empty( $meta[$key] ) ) {
				$target_id = $meta[$key];
				break;
			}
		}

		if ( 'unknown' === $target_id ) {
			$target_id = 'alert_' . ($id ?: 'none');
		}

		return array(
			'id' => $target_id,
			'type' => $target_type,
		);
	}

	private function is_post( array $meta ): bool
	{
		return ! empty( $meta['PostType'] ) && ! empty( $meta['PostID'] );
	}

	private function is_plugin( array $meta, string $object ): bool
	{
		return 'plugin' === $object
			|| isset( $meta['PluginData'] )
			|| isset( $this->plugins[$object] );
	}

	private function plugin_name( array $meta, string $object ): string
	{
		return $meta['PluginData']?->Name
			?? $this->plugins[$object]
			?? 'unknown';
	}

	private function is_theme( array $meta, string $object ): bool
	{
		return 'theme' === $object
			|| isset( $meta['Theme'] )
			|| isset( $this->themes[$object] );
	}

	private function theme_name( array $meta, string $object ): string
	{
		return $meta['Theme']?->Name
			?? $this->themes[$object]
			?? 'unknown';
	}
}
