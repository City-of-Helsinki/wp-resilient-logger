<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Helpers;

use WpSecurityAuditLog;
use WSAL\Controllers\Alert_Manager;
use WP\helfi_resilient_logger\ResilientLogger;
use ResilientLogger\Utils\HumanReadableDiffer;

class WSALAugment {
  private static ?self $instance = null;

  private HumanReadableDiffer $differ;

  /** Stores previous post contents here for diff as array [postId => postContents] */
  private array $prevContents;

  private const WSAL_PRUNING_UNIT    = 'wsal_pruning-unit';
  private const WSAL_PRUNING_DATE    = 'wsal_pruning-date';
  private const WSAL_PRUNING_ENABLED = 'wsal_pruning-date-e';
  private const WSAL_DISABLED_ALERTS = 'wsal_disabled-alerts';

  private static $enforcedSettings = [
    self::WSAL_PRUNING_UNIT    => 'months',
    self::WSAL_PRUNING_DATE    => '6 months',
    self::WSAL_PRUNING_ENABLED => 'yes',
    //self::WSAL_DISABLED_ALERTS => [0, 8825, 8845],
  ];

  public function __construct() {
    $this->differ = new HumanReadableDiffer();
    $this->prevContents = [];
  }

  public static function getInstance(): self {
    if (self::$instance == null) {
      self::$instance = new WSALAugment();
    }

    return self::$instance;
  }

  public static function shouldEnforceSettings(): bool {
    $settings = ResilientLogger::getSettings();
    return (bool)($settings['enforce_wsal_settings'] ?? false);
  }

  public static function isWsalInstalled(): bool {
    return class_exists(WpSecurityAuditLog::class) && class_exists(Alert_Manager::class);
  }

  public function enforceSettings(): void {
    if (!self::shouldEnforceSettings()) {
      return;
    }

    \add_filter('wsal_user_can', function(bool $result, mixed $user, string $action): bool {
      return $this->checkUserPermissions($result, $user, $action);
    }, PHP_INT_MAX, 3);

    \add_action('pre_post_update', function(int $post_id) {
      return $this->captureOldContents($post_id);
    }, 10, 1);

    \add_filter('wsal_event_data_before_log', function(array $data) {
      return $this->augmentEventData($data);
    }, PHP_INT_MAX, 1 );

    foreach (self::$enforcedSettings as $name => $enforced_value) {
      \add_filter("pre_option_{$name}", function($db_value) use ($name, $enforced_value) {
          return $this->overrideSettingsValue($name, $db_value, $enforced_value);
      }, PHP_INT_MAX, 1);
    }
  }

  private function captureOldContents(int $post_id): void {
      $contents = self::getPostContents($post_id);
      
      if ($contents != null) {
          $this->prevContents[$post_id] = $contents;
      }
  }

  private static function getPostContents(int $post_id): ?string {
      $post = \get_post($post_id);

      if ($post instanceof \WP_Post) {
          return $post->post_content ?? null;
      }

      return null;
  }

  private function checkUserPermissions(bool $result, \WP_User $user, string $action): bool {
    if ($action === 'edit') {
      return false;
    }

    return $result;
  }

  private function augmentEventData(array $data): array {
      $post_id = $data['PostID'] ?? null;

      if ($post_id && isset($this->prevContents[(int)$post_id])) {
        $oldContents = $this->prevContents[(int)$post_id];
        $newContents = self::getPostContents($post_id) ?? '';

        $data['ContentDiff'] = $this->generateTextDiff($oldContents, $newContents);
        unset($this->prevContents[(int)$post_id]);
      }

      $data['RequestID'] = self::headerOrDefaultValue("X-Request-ID", "N/A");
      return $data;
  }

  private function overrideSettingsValue(string $option_name, mixed $db_value, mixed $enforced_value): mixed {
    return $enforced_value;
  }

  /**
   * Retrieves a specific HTTP header or returns a default value.
   *
   * @param string $headerName  The header name (e.g., 'X-Request-ID').
   * @param mixed  $default     The value to return if the header is missing.
   * @return string
   */
  private static function headerOrDefaultValue($headerName, $default) {
      // Normalize the name to the PHP $_SERVER format
      // Example: 'X-Request-ID' becomes 'HTTP_X_REQUEST_ID'
      $formattedHeaderName = 'HTTP_' . strtoupper(str_replace( '-', '_', $headerName));

      if (isset($_SERVER[$formattedHeaderName]) && !empty($_SERVER[$formattedHeaderName])) {
          return sanitize_text_field($_SERVER[$formattedHeaderName]);
      }

      return $default;
  }

  private function generateTextDiff(string $old, string $new): string {
      try {
          return $this->differ->diff($old, $new);
      } catch (\Exception $e) {
          return "Error generating diff: " . $e->getMessage();
      }
  }
}

?>