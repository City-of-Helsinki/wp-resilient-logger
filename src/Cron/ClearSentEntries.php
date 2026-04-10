<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Cron;

use WP\helfi_resilient_logger\ResilientLogger;

class ClearSentEntries {
    public static function trigger(): void {
        $settings = ResilientLogger::getSettings();
        $enabled = (bool) ($settings['clear_sent_entries'] ?? false);
        $daysToKeep = (int) ($settings['days_to_keep'] ?? 30);

        if ($enabled) {
            try {
                ResilientLogger::getInstance()->clearSentEntries($daysToKeep);
            } catch (\Exception $e) {
                error_log(sprintf("ResilientLogger error: %s", $e->getMessage()));
            }
        }
    }
}
