<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Commands;

use WP\helfi_resilient_logger\ResilientLogger;

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
class ClearSentEntries {
  /**
   * Default value for how many days the old entries should be kept in DB.
   *  
   * Note: not every source will support clearing old ones, especially
   * when accessing external services directly via DB like WSAL.
   */
  private const DEFAULT_DAYS_TO_KEEP = 30;

  /**
   * Executes the cleanup command.
   *
   * @param array $args       Positional arguments.
   * @param array $assoc_args Associative arguments.
   */
  public function __invoke($args, $assocArgs) {
    $settings = ResilientLogger::getSettings();
    $shouldClear = (bool) ( $settings['clear_sent_entries'] ?? false );

    if (!$shouldClear) {
      \WP_CLI::error( "clear_sent_entries is disabled in config" );
    }

    // Priority: CLI Flag > Settings Array > Default
    $daysToKeep = (int) ( 
      $assocArgs['days-to-keep'] ?? 
      $settings['days_to_keep'] ?? 
      self::DEFAULT_DAYS_TO_KEEP 
    );

    \WP_CLI::log(sprintf("Begin clear_sent_entries job (keeping last %d days).", $daysToKeep));

    try {
      ResilientLogger::getInstance()->clearSentEntries($daysToKeep);
      \WP_CLI::success(sprintf("Finished clear_sent_entries."));
    } catch ( \Exception $e ) {
      \WP_CLI::error(sprintf("Cleanup failed: %s", $e->getMessage()));
    }
  }
}