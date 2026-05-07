<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Settings;

enum WSALForcedSettings: string
{
	case PRUNING_UNIT = 'wsal_pruning-unit';
	case PRUNING_DATE = 'wsal_pruning-date';
	case PRUNING_ENABLED = 'wsal_pruning-date-e';

	public function override(): mixed
    {
        return match( $this ) {
            self::PRUNING_UNIT => 'months',
            self::PRUNING_DATE => '6 months',
            self::PRUNING_ENABLED => 'yes',
        };
    }
}
