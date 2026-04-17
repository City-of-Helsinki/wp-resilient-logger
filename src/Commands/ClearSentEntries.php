<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Commands;

/**
 * Clear wp-resilient-logger entries which are already submitted,
 * only clear if settings['clear_sent_entries'] is set to true (default: false)
 *
 * ## OPTIONS
 *
 * [--days-to-keep=<number>]
 * : Days to keep the old values stored. If not provided, uses plugin settings or defaults to 30.
 *
 * ## EXAMPLES
 *
 * wp resilient-logger clear_sent_entries --days-to-keep=15
 */
class ClearSentEntries
{
  /**
   * Executes the cleanup command.
   *
   * @param array $args       Positional arguments.
   * @param array $assoc_args Associative arguments.
   */
	public function __invoke($args, $assocArgs) {
		try {
			\do_action( 'helsinki_wp_resilient_logger_clear_sent_entries' );

			\WP_CLI::success(sprintf("Finished clear_sent_entries."));
		} catch ( \Exception $e ) {
			\WP_CLI::error(sprintf("Cleanup failed: %s", $e->getMessage()));
		}
	}
}
