<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Settings;

enum WSALForcedSettings: string
{
	case PRUNING_UNIT = 'wsal_pruning-unit';
	case PRUNING_DATE = 'wsal_pruning-date';
	case PRUNING_ENABLED = 'wsal_pruning-date-e';

	case LOG_TIMEZONE = 'wsal_timezone';
	case LOG_URL_PARAMETERS = 'wsal_url_parameters';
	case LOG_DETAILS_LEVEL = 'wsal_details-level';

	case HIDE_PLUGIN = 'wsal_hide-plugin';

	case LOG_ACCESS = 'wsal_restrict-log-viewer';

	case SETTINGS_ACCESS = 'wsal_restrict-plugin-settings';
	case SETTINGS_ONLY_ME_ID = 'wsal_only-me-user-id';

	case EXCLUDED_IP = 'wsal_excluded-ip';
	case EXCLUDED_POST_META = 'wsal_excluded-post-meta';
	case EXCLUDED_POST_TYPES = 'wsal_custom-post-types';
	case EXCLUDED_POST_STATUS = 'wsal_excluded-post-status';
	case EXCLUDED_ROLES = 'wsal_excluded-roles';
	case EXCLUDED_USERS = 'wsal_excluded-users';
	case EXCLUDED_USER_META = 'wsal_excluded-user-meta';

	case DELETE_ON_UNINSTALL = 'wsal_delete-data';

	public function override(): mixed
    {
        return match( $this ) {
            self::PRUNING_UNIT => 'months',
            self::PRUNING_DATE => '6 months',
            self::PRUNING_ENABLED => 'yes', // no until sent?

			self::LOG_TIMEZONE => 'wp',
			self::LOG_URL_PARAMETERS => 'yes',
			self::LOG_DETAILS_LEVEL => 'geek',

			self::HIDE_PLUGIN => 'no',
			self::SETTINGS_ACCESS => 'only_admins',
			self::SETTINGS_ONLY_ME_ID => null,
			self::LOG_ACCESS => 'only_admins',

			self::EXCLUDED_IP => '',
			self::EXCLUDED_POST_META => '',
			self::EXCLUDED_POST_TYPES => '',
			self::EXCLUDED_POST_STATUS => '',
			self::EXCLUDED_ROLES => '',
			self::EXCLUDED_USERS => '',
			self::EXCLUDED_USER_META => '',

			self::DELETE_ON_UNINSTALL => 'no',
        };
    }

	public static function is_forced_setting_text( string $text ): bool
	{
		return match( $text ) {
			'Restrict plugin access',
			'Hide plugin in plugins page',
			'Activity log retention',
			'Events timestamp',
			'Show Query string details in event metadata',
			'Exclude users:',
			'Exclude roles:',
			'Exclude IP address(es):',
			'Exclude post type:',
			'Exclude post status:',
			'Exclude custom post fields:',
			'Exclude custom user fields:',
			'Reset Settings',
			'Purge Activity Log',
			'Remove data on uninstall' => true,
			default => false,
		};
	}
}
