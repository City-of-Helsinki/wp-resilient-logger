<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Cron;

final class ClearSentEntries implements ScheduledAction
{
	public function action(): string
	{
		return 'resilient_logger.clear_sent_entries';
	}

	public function interval(): ResilientLoggerSchedule
	{
		return ResilientLoggerSchedule::THIRTY_DAYS;
	}

    public function trigger(): void
	{
		try {
			\do_action( 'helsinki_wp_resilient_logger_clear_sent_entries' );
		} catch (\Exception $e) {
			error_log( sprintf(
				'ResilientLogger error: %s',
				$e->getMessage()
			) );
		}
    }
}
