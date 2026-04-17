<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Cron;

class SubmitUnsentEntries {
    public static function trigger(): void {
		try {
			\do_action( 'helsinki_wp_resilient_logger_submit_unsent_entries' );
		} catch (\Exception $e) {
			error_log(sprintf("ResilientLogger error: %s", $e->getMessage()));
		}
    }
}
