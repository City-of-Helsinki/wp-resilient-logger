<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Settings;

enum WSALDisabledAlerts: int
{
	// Content
	case VIEW_POST_EDIT = 2100;
	case VIEW_POST = 2101;
	case VIEW_PAGE_EDIT = 2102;
	case VIEW_PAGE = 2103;
	case VIEW_CPT_EDIT = 2104;
	case VIEW_CPT = 2105;
	case VIEW_PROTECTED_POST = 2134;

	// Updates
	case AVAILABLE_PLUGIN_UPDATE = 5032;
	case AVAILABLE_THEME_UPDATE = 5033;
	case AVAILABLE_CORE_UPDATE = 6079;

	// Cron
	case RECURRING_TASK_EXECUTED = 6070;

	public static function codes(): array
    {
        return array_map( fn($case) => $case->value, self::cases() );
    }
}
