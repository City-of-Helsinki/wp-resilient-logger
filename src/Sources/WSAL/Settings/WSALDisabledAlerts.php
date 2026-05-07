<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Settings;

enum WSALDisabledAlerts: int
{
	// WordPress
	case VIEW_POST = 2101;
	case VIEW_PAGE_EDIT = 2102;
	case VIEW_PAGE = 2103;
	case VIEW_CPT_EDIT = 2104;
	case VIEW_CPT = 2105;
	case VIEW_PROTECTED_POST = 2134;

	public static function codes(): array
    {
        return array_map( fn($case) => $case->value, self::cases() );
    }
}
