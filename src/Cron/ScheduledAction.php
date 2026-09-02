<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Cron;

interface ScheduledAction
{
	public function action(): string;
	public function interval(): ResilientLoggerSchedule;
    public function trigger(): void;
}
