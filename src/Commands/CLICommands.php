<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Commands;

use WP\helfi_resilient_logger\Commands\SubmitUnsentEntries;
use WP\helfi_resilient_logger\Commands\ClearSentEntries;

class CLICommands {
    public static function useCliCommands(): bool {
        return defined('WP_CLI') && WP_CLI;
    }

    public static function register(): void {
        if (self::useCliCommands()) {
            \WP_CLI::add_command('resilient-logger submit_unsent_entries', SubmitUnsentEntries::class);
            \WP_CLI::add_command('resilient-logger clear_sent_entries',  ClearSentEntries::class);
        }
    }
}

