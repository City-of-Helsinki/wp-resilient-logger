<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger;

use Psr\Log\LoggerInterface;
use ResilientLogger\ResilientLogger as ResilientLoggerBase;
use ResilientLogger\Types;
use CityOfHelsinki\WP\ResilientLogger\Helpers\InternalLogger;

/**
 * @phpstan-import-type ResilientLoggerOptions from Types
 */
class ResilientLogger extends ResilientLoggerBase {
  /**
   * Per-site possible overrides for default configuration.
   */
  const SITE_OVERRIDE_OPTIONS = [
    'origin'                         => 'helfi_resilient_logger_origin',
    'environment'                    => 'helfi_resilient_logger_environment',
    'store_old_entries_days'         => 'helfi_resilient_logger_store_old_entries_days',
    'batch_limit'                    => 'helfi_resilient_logger_batch_limit',
    'chunk_size'                     => 'helfi_resilient_logger_chunk_size',
    'submit_unsent_entries'          => 'helfi_resilient_logger_submit_unsent_entries',
    'clear_sent_entries'             => 'helfi_resilient_logger_clear_sent_entries',
  ];

  private static ?ResilientLogger $instance = null;
  private static ?LoggerInterface $internalLogger = null;

  /** 
   * Clears the singleton instance. 
   * Required for multi-site setups since settings will be trying to read site specific data.
   */
  public static function reset(): void {
    self::$instance = null;
  }

  /**
   * Get the singleton instance.
   */
  public static function getInstance(): ResilientLogger {
    if (self::$instance === null) {
      self::$instance = self::createFromSettings();
    }

    return self::$instance;
  }

  /**
   * Resolves the final settings by merging the constant with site-specific overrides.
   * @return ResilientLoggerOptions
   */
  public static function getSettings(): array {
    if (!defined('RESILIENT_LOGGER_SETTINGS')) {
      throw new \RuntimeException('"RESILIENT_LOGGER_SETTINGS" is missing');
    }

    /** @var ResilientLoggerOptions $settings */
    $settings = constant('RESILIENT_LOGGER_SETTINGS');
    
    foreach (self::SITE_OVERRIDE_OPTIONS as $key => $option) {
      // Ensure the key exists in the constant to provide a fallback type
      $fallback = $settings[$key] ?? ''; 
      $settings[$key] = self::parseOption($option, $fallback);
    }

    return $settings;
  }

  /**
   * @return static
   */
  public static function createFromSettings(): static {
    $instance = static::create(self::getSettings());
    parent::setInternalLogger(new InternalLogger());
    return $instance;
  }

  private static function parseOption(string $key, mixed $fallback): mixed {
    $override = \get_option($key);
    
    if ($override !== false && is_string($override) && trim($override) !== '') {
      settype($override, gettype($fallback));
      return $override;
    }

    return $fallback;
  }

  /**
   * Internal logging for plugin-specific events.
   * Writes to the standard PHP/WordPress error log.
   */
  public static function logInternal(string $level, string $format, ...$args): void {
    $level = strtoupper($level);
    $message = empty($args) ? $format : sprintf($format, ...$args);
    $formatted = sprintf('[wp-resilient-logger] [%s] %s', $level, $message);
    
    if ($level === 'ERROR' || (defined('WP_DEBUG') && WP_DEBUG)) {
      error_log($formatted);
    }

    if (defined('WP_CLI') && WP_CLI) {
      // Red for error, Yellow for info
      $color = ($level === 'ERROR') ? '%R' : '%Y';
      $colorized = \WP_CLI::colorize("{$color}{$formatted}%n");
      \WP_CLI::log($colorized);
    }
  }
}

?>