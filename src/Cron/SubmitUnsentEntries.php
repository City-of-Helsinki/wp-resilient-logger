<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Cron;

final class SubmitUnsentEntries implements ScheduledAction
{
	public function action(): string
	{
		return 'resilient_logger.submit_unsent_entries';
	}

	public function interval(): ResilientLoggerSchedule
	{
		return ResilientLoggerSchedule::FIFTEEN_MINUTES;
	}

    public function trigger(): void
	{
		try {
			\do_action( 'helsinki_wp_resilient_logger_submit_unsent_entries' );
		} catch (\Exception $e) {
			error_log( sprintf(
				'ResilientLogger error: %s',
				$e->getMessage()
			) );
		}
    }
}
