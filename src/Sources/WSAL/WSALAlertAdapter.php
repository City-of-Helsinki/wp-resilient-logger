<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL;

final class WSALAlertAdapter
{
	const array WSAL_TARGET_MAP = [
		'PostID'     => 'post',
		'UserID'     => 'user',
		'TermID'     => 'taxonomy_term',
		'CommentID'  => 'comment',
		'MenuID'     => 'menu',
		'OptionName' => 'setting',
		'RoleName'   => 'user_role',
		'PluginFile' => 'plugin',
		'ThemeName'  => 'theme',
		'FileName'   => 'file',
	];

	/**
	 * Maps the alert ID to a human-readable operation and description.
	 *
	 * Generated from https://melapress.com/support/kb/wp-activity-log-list-event-ids/
	 * with AI assistance — may not always be correct.
	 *
	 * @return array{operation: string, description: string}
	 */
	public function parseAlertDetails(int $id): array
	{
		$specialCases = [
			// CREATES
			2000 => ['CREATE', 'Created a new post'],
			2001 => ['CREATE', 'Published a post'],
			2023 => ['CREATE', 'Created a new category'],
			2042 => ['CREATE', 'Added a new widget'],
			2078 => ['CREATE', 'Created a menu'],
			2121 => ['CREATE', 'Created a new tag'],
			4000 => ['CREATE', 'New user created'],
			4001 => ['CREATE', 'User created a new user'],
			4012 => ['CREATE', 'Created a new network user'],
			5000 => ['CREATE', 'Installed a plugin'],
			5005 => ['CREATE', 'Installed a theme'],
			6314 => ['CREATE', 'WSAL: Custom notification added'],
			6317 => ['CREATE', 'WSAL: SMS notification added'],
			// READS
			1000 => ['READ', 'User successfully logged in'],
			1008 => ['READ', 'Switched to another user'],
			2100 => ['READ', 'Opened a post in editor'],
			2101 => ['READ', 'Viewed a post'],
			4014 => ['READ', 'Opened a user profile page'],
			6069 => ['READ', 'Cron task executed'],
			// DELETES
			2008 => ['DELETE', 'Permanently deleted a post'],
			2011 => ['DELETE', 'Deleted a file'],
			2012 => ['DELETE', 'Moved a post to trash'],
			2024 => ['DELETE', 'Deleted a category'],
			2044 => ['DELETE', 'Deleted a widget'],
			2081 => ['DELETE', 'Deleted a menu'],
			2096 => ['DELETE', 'Moved a comment to trash'],
			2098 => ['DELETE', 'Permanently deleted a comment'],
			2122 => ['DELETE', 'Deleted a tag'],
			4007 => ['DELETE', 'Deleted a user'],
			5003 => ['DELETE', 'Uninstalled a plugin'],
			5007 => ['DELETE', 'Deleted a theme'],
			6030 => ['DELETE', 'File deleted from website'],
			6034 => ['DELETE', 'Activity log purged'],
			6316 => ['DELETE', 'WSAL: Custom notification deleted'],
			6319 => ['DELETE', 'WSAL: SMS notification deleted'],
		];

		$details = match (true) {
			isset($specialCases[$id])          => $specialCases[$id],
			$id >= 1000 && $id <= 1999         => ['READ',    'User Session/Auth Activity'],
			$id >= 2000 && $id <= 2999         => ['UPDATE',  'Content/Post Modification'],
			$id >= 4000 && $id <= 4999         => ['UPDATE',  'User Profile Update'],
			$id >= 5000 && $id <= 5999         => ['UPDATE',  'Plugin/Theme Management'],
			$id >= 6000 && $id <= 6999         => ['UPDATE',  'System/WSAL Settings Change'],
			$id >= 8000 && $id <= 9999         => ['UPDATE',  'Extension/WooCommerce Action'],
			default => (function () use ($id) {
				\do_action( 'helsinki_wp_resilient_logger_unknown_alert_id', $id, get_class($this) );

				return ['UNKNOWN', "Activity Log Event ({$id})"];
			})(),
		};

		return ['operation' => $details[0], 'description' => $details[1]];
	}

	/**
	 * @param array<string, string> $meta
	 * @return array{id: string|int, type: string, site_id: int|string}
	 */
	public function parseTarget(string|int $id, string|int $site_id, array $meta): array
	{
		$targetId   = 'unknown';
		$targetType = 'unmapped_target';

		foreach (self::WSAL_TARGET_MAP as $metaKey => $typeHint) {
			if (isset($meta[$metaKey])) {
				$targetId   = $meta[$metaKey];
				$targetType = $typeHint;
				break;
			}
		}

		return [
			'id'      => $targetId === 'unknown' ? 'alert_' . $id : $targetId,
			'type'    => $targetType,
			'site_id' => $site_id,
		];
	}
}
