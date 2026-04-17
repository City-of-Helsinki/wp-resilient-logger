<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Commands;

/**
 * Send wp-resilient-logger entries to centralized log center
 *
 * ## EXAMPLES
 *
 * wp resilient-logger submit_unsent_entries
 */
class SubmitUnsentEntries
{
	public function __invoke( $args, $assoc_args )
	{
	    \WP_CLI::log( "Begin submit_unsent_entries job.");

		try {
			\do_action( 'helsinki_wp_resilient_logger_submit_unsent_entries' );

			\WP_CLI::success("Successfully submitted unset entries.");
		} catch (\Exception $e) {
			\WP_CLI::error(sprintf("Submission failed: %s", $e->getMessage()));
		}
	}
}
