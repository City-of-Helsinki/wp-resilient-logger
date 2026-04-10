<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Cron;

use WP\helfi_resilient_logger\ResilientLogger;

class SubmitUnsentEntries {
    public static function trigger(): void {
        $settings = ResilientLogger::getSettings();
        $enabled = (bool) ($settings['submit_unsent_entries'] ?? false);

        if ($enabled) {
            try {
                $results = ResilientLogger::getInstance()->submitUnsentEntries();
                $failures = array_filter($results, fn($success) => !$success);

                if (!empty($failures)) {
                    error_log(sprintf(
                        "ResilientLogger warning: %d submissions failed. IDs: %s",
                        count($failures),
                        implode(', ', array_keys($failures))
                    ));
                }
            } catch (\Exception $e) {
                error_log(sprintf("ResilientLogger error: %s", $e->getMessage()));
            }
        }
    }
}
