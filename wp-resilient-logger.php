<?php
/**
 * Plugin Name: Helfi Resilient Logger
 */

declare(strict_types=1);

use WP\helfi_resilient_logger\Bootstrap;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Bootstrap the plugin
Bootstrap::setup(__FILE__);