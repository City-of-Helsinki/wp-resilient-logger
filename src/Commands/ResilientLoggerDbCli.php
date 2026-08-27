<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Commands;

use Exception;
use WP_CLI;

final class ResilientLoggerDbCli
{
	/**
	 * Reset wp-resilient-logger database tables.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
 	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Reset database.
	 *     $ wp resilient-logger db reset --yes
	 *     Success: The database has been reset.
	 *
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 *
	 * @subcommand reset
	 */
	public function reset( $args, $assoc_args ): void
	{
		if ( \has_action( 'helsinki_wp_resilient_logger_db_reset' ) ) {
			WP_CLI::confirm( 'Are you sure you want to reset the Resilient Logger database?', $assoc_args );

			try {
				\do_action( 'helsinki_wp_resilient_logger_db_reset' );

				WP_CLI::success( 'The database has been reset.' );
			} catch ( Exception $e ) {
				WP_CLI::error( sprintf( 'Reset failed: %s', $e->getMessage() ) );
			}
		} else {
			WP_CLI::warning( 'No reset actions available.' );
		}
	}
}
