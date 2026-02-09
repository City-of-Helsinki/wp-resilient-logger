<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Commands;

use WP\helfi_resilient_logger\ResilientLogger;

/**
 * Send wp-resilient-logger entries to centralized log center
 *
 * ## EXAMPLES
 *
 * wp resilient-logger submit_unsent_entries
 */
class SubmitUnsentEntries {
  public function __invoke( $args, $assoc_args ) {
    $settings = ResilientLogger::getSettings();
    $shouldSubmit = (bool) ($settings['submit_unsent_entries'] ?? false);

    if (!$shouldSubmit) {
      \WP_CLI::error("submit_unsent_entries is disabled in config");
    }

    \WP_CLI::log( "Begin submit_unsent_entries job.");

    try {
      $results = ResilientLogger::getInstance()->submitUnsentEntries();
      $successes = array_filter($results);
      $failures = array_diff_key($results, $successes);

      $successCount = count($successes);
      $failureCount = count($failures);

      if ($failureCount > 0) {
        \WP_CLI::warning(sprintf(
            "Processed %d entries: %d succeeded, %d failed.", 
            count($results), 
            $successCount, 
            $failureCount 
        ));
        
        \WP_CLI::warning(sprintf("Failed IDs: %s", implode(', ', array_keys($failures))));
      } else {
        \WP_CLI::success(sprintf("Successfully processed %d entries.", $successCount));
      }
    } catch (\Exception $e ) {
      \WP_CLI::error(sprintf("Submission failed: %s", $e->getMessage()));
    }
  }
}