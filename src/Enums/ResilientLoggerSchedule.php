<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Enums;

enum ResilientLoggerSchedule: string
{
	case FIFTEEN_MINUTES = 'fifteen_minutes';
    case THIRTY_DAYS = 'thirty_days';

	public function interval(): string
    {
        return match( $this ) {
            self::FIFTEEN_MINUTES => 15 * \MINUTE_IN_SECONDS,
            self::THIRTY_DAYS => 30 * \DAY_IN_SECONDS,
        };
    }

	public function label(): string
    {
        return match( $this ) {
            self::FIFTEEN_MINUTES => __( 'Every 15 Minutes', 'wp-resilient-logger' ),
            self::THIRTY_DAYS => __( 'Every 30 Days', 'wp-resilient-logger' ),
        };
    }
}
