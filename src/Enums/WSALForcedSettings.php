<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Enums;

enum WSALForcedSettings: string
{
	case PRUNING_UNIT = 'wsal_pruning-unit';
	case PRUNING_DATE = 'wsal_pruning-date';
	case PRUNING_ENABLED = 'wsal_pruning-date-e';
	// case DISABLED_ALERTS = 'wsal_disabled-alerts';

	public function override(): mixed
    {
        return match( $this ) {
            self::PRUNING_UNIT => 'months',
            self::PRUNING_DATE => '6 months',
            self::PRUNING_ENABLED => 'yes',
            // self::DISABLED_ALERTS => [0, 8825, 8845],
        };
    }
}
