<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Commands;

if (!defined('WP_CLI')) {
    return;
}

use WP\helfi_resilient_logger\Commands\SubmitUnsentEntries;
use WP\helfi_resilient_logger\Commands\ClearSentEntries;

\WP_CLI::add_command('resilient-logger submit_unsent_entries', SubmitUnsentEntries::class);
\WP_CLI::add_command('resilient-logger clear_sent_entries',  ClearSentEntries::class);