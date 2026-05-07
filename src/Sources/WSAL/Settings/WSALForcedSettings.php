<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Settings;

enum WSALForcedSettings: string
{
	case PRUNING_UNIT = 'wsal_pruning-unit';
	case PRUNING_DATE = 'wsal_pruning-date';
	case PRUNING_ENABLED = 'wsal_pruning-date-e';

	case LOG_TIMEZONE = 'wsal_timezone';
	case LOG_MILLISECONDS = 'wsal_show_milliseconds';
	case LOG_URL_PARAMETERS = 'wsal_url_parameters';
	case LOG_DETAILS_LEVEL = 'wsal_details-level';

	// case LOGIN_NOTIFICATION = 'wsal_login_page_notification';
	// case LOGIN_NOTIFICATION_TEXT = 'wsal_login_page_notification_text';

	case HIDE_PLUGIN = 'wsal_hide-plugin';

	case LOG_ACCESS = 'wsal_restrict-log-viewer';
	// case LOG_VIEWERS = 'wsal_plugin-viewers';

	case SETTINGS_ACCESS = 'wsal_restrict-plugin-settings';
	case SETTINGS_ONLY_ME_ID = 'wsal_only-me-user-id';

	case USE_EMAIL = 'wsal_use-email';
	case EMAIL_NAME = 'wsal_display-name';
	case EMAIL_FROM = 'wsal_from-email';

	case EXCLUDED_IP = 'wsal_excluded-ip';
	case EXCLUDED_POST_META = 'wsal_excluded-post-meta';
	case EXCLUDED_POST_TYPES = 'wsal_custom-post-types';
	case EXCLUDED_POST_STATUS = 'wsal_excluded-post-status';
	case EXCLUDED_ROLES = 'wsal_excluded-roles';
	case EXCLUDED_USERS = 'wsal_excluded-users';
	case EXCLUDED_USER_META = 'wsal_excluded-user-meta';

	public function override(): mixed
    {
        return match( $this ) {
            self::PRUNING_UNIT => 'months',
            self::PRUNING_DATE => '6 months',
            self::PRUNING_ENABLED => 'yes',

			self::LOG_TIMEZONE => 'wp', // or utc
			self::LOG_MILLISECONDS => 'yes',
			self::LOG_URL_PARAMETERS => 'yes',
			self::LOG_DETAILS_LEVEL => 'geek',

			// self::LOGIN_NOTIFICATION => 'no',
			// self::LOGIN_NOTIFICATION_TEXT => '',

			self::HIDE_PLUGIN => 'no',
			self::SETTINGS_ACCESS => 'only_admins',
			self::SETTINGS_ONLY_ME_ID => null,
			self::LOG_ACCESS => 'only_admins',
			// self::LOG_VIEWERS => '',

			self::USE_EMAIL => 'default_email',
			self::EMAIL_NAME => '',
			self::EMAIL_FROM => '',

			self::EXCLUDED_IP => '',
			self::EXCLUDED_POST_META => '',
			self::EXCLUDED_POST_TYPES => '',
			self::EXCLUDED_POST_STATUS => '',
			self::EXCLUDED_ROLES => '',
			self::EXCLUDED_USERS => '',
			self::EXCLUDED_USER_META => '',
        };
    }
}
