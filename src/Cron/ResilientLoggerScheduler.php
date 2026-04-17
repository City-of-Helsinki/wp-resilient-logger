<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Cron;

use WP\helfi_resilient_logger\Enums\ResilientLoggerSchedule;

final class ResilientLoggerScheduler
{
	public function handlers(): array
	{
		return array_reduce(
			$this->scheduled_actions(),
			function( array $actions, ScheduledAction $action ) {
				$actions[$action->action()] = array( $action, 'trigger' );

				return $actions;
			},
			array()
		);
	}

	public function schedules(): array
	{
		return array_reduce(
			$this->scheduled_actions(),
			function( array $actions, ScheduledAction $action ) {
				$actions[$action->action()] = $action->interval()->value;

				return $actions;
			},
			array()
		);
	}

	private function scheduled_actions(): array
	{
		return array_map(
			fn( string $action ) => new $action(),
			array(
				ClearSentEntries::class,
				SubmitUnsentEntries::class,
			)
		);
	}
}
