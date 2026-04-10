<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger;

use WP\helfi_resilient_logger\Entities\ResilientLogEntity;
use WP\helfi_resilient_logger\Entities\WSALSyncEntity;
use WP\helfi_resilient_logger\Helpers\WSALAugment;
use WP\helfi_resilient_logger\Cron\CronTasks;
use WP\helfi_resilient_logger\Commands\CLICommands;

class Bootstrap {
  public static function setup(string $pluginFilePath): void {
    // Initialization and activation
    \register_activation_hook($pluginFilePath, [self::class, 'plugin_activate']);
    \add_action('wp_initialize_site', [self::class, 'init_multisite']);

    CLICommands::register();
    CronTasks::register();

    if (WSALAugment::shouldEnforceSettings()) {
      WSALAugment::getInstance()->enforceSettings();
    }
  }

  /**
   * Entry point for plugin activation.
   */
  public static function plugin_activate(): void {
    if (\is_multisite()) {
      foreach (\get_sites(['fields' => 'ids', 'number' => 0]) as $site_id) {
        self::install_for_site_id((int) $site_id);
      }
    } else {
        self::install_for_current_site();
    }
  }

  /**
   * Entry point for new site creation in Multisite.
   */
  public static function init_multisite(\WP_Site $site): void {
    self::install_for_site_id((int) $site->blog_id);
  }

  /**
   * Internal helper to handle the blog switching logic.
   */
  private static function install_for_site_id(int $site_id): void {
    \switch_to_blog($site_id);

    try {
      self::install_for_current_site();
    } finally {
      \restore_current_blog();
    }
  }

  /**
   * The actual "workhorse" that creates the tables.
   */
  private static function install_for_current_site(): void {
      ResilientLogEntity::install();

      if (WSALAugment::isWsalInstalled()) {
          WSALSyncEntity::install();
      }
  }
}