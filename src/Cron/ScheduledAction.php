<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Cron;

interface ScheduledAction
{
	public function action(): string;
	public function interval(): ResilientLoggerSchedule;
    public function trigger(): void;
}
