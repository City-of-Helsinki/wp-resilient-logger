<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Cron;

class CronTasks {
    private const TASK_SUBMIT_UNSENT_ENTRIES = 'resilient_logger.submit_unsent_entries';
    private const TASK_CLEAR_SENT_ENTRIES = 'resilient_logger.clear_sent_entries';

    public static function useWordpressCron(): bool {
        return defined('RESILIENT_LOGGER_USE_WP_CRON') && RESILIENT_LOGGER_USE_WP_CRON;
    }

    public static function registerCronSchedules(array $schedules) {
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => 'Every 15 Minutes',
        );

        $schedules['thirty_days'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => 'Every 30 Days',
        );

        return $schedules;
    }

    public static function register(): void {
        \add_filter('cron_schedules', [self::class, 'registerCronSchedules']);
        \add_action(self::TASK_SUBMIT_UNSENT_ENTRIES, [SubmitUnsentEntries::class, 'trigger']);
        \add_action(self::TASK_CLEAR_SENT_ENTRIES, [ClearSentEntries::class, 'trigger']);

        if (self::useWordpressCron()) {
            if (!\wp_next_scheduled(self::TASK_SUBMIT_UNSENT_ENTRIES)) {
                \wp_schedule_event(time(), 'fifteen_minutes', self::TASK_SUBMIT_UNSENT_ENTRIES);
            }
            if (!\wp_next_scheduled(self::TASK_CLEAR_SENT_ENTRIES)) {
                \wp_schedule_event(time(), 'thirty_days', self::TASK_CLEAR_SENT_ENTRIES);
            }
        } else {
            \wp_clear_scheduled_hook(self::TASK_SUBMIT_UNSENT_ENTRIES);
            \wp_clear_scheduled_hook(self::TASK_CLEAR_SENT_ENTRIES);
        }
    }
}
