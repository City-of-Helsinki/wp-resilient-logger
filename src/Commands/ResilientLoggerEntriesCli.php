<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Commands;

use Exception;
use WP_CLI;

final class ResilientLoggerEntriesCli
{
	/**
	 * Clear wp-resilient-logger entries which are already submitted,
	 * only clear if settings['clear_sent_entries'] is set to true (default: false)
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 * wp resilient-logger entries clear
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 *
	 * @subcommand clear
	 */
	private function clear( $args, $flags ): void
	{
		try {
			\do_action( 'helsinki_wp_resilient_logger_clear_sent_entries' );

			WP_CLI::success( 'Finished clear_sent_entries.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf( 'Cleanup failed: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Send wp-resilient-logger entries to centralized log center
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 * wp resilient-logger entries submit
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 *
	 * @subcommand submit
	 */
	private function submit( $args, $flags ): void
	{
		WP_CLI::log( 'Begin submit_unsent_entries job.' );

		try {
			\do_action( 'helsinki_wp_resilient_logger_submit_unsent_entries' );

			WP_CLI::success( 'Successfully submitted unset entries.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf( 'Submission failed: %s', $e->getMessage() ) );
		}
	}
}
