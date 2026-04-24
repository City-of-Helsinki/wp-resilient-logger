<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use CityOfHelsinki\WP\ResilientLogger\Database\ResilientLoggerTables;
use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerAdapter;
use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use CityOfHelsinki\WP\ResilientLogger\Cron\ResilientLoggerScheduler;
use CityOfHelsinki\WP\ResilientLogger\Helpers\CurrentEnvironment;
use CityOfHelsinki\WP\ResilientLogger\Helpers\WSALAugment;
use CityOfHelsinki\WP\ResilientLogger\Database\Migrator;
use CityOfHelsinki\WP\ResilientLogger\Sources\Native\ResilientLoggerData;
use CityOfHelsinki\WP\ResilientLogger\Sources\Native\ResilientLoggerLogSource;
use CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\WSALAlertAdapter;
use CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\WSALData;
use CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\WSALLogSource;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Utils\HumanReadableDiffer;

function helsinki_wp_resilient_logger(): ResilientLoggerAdapter {
	return new ResilientLoggerAdapter(
		helsinki_wp_resilient_logger_config(),
	);
}

function helsinki_wp_resilient_logger_environment(): CurrentEnvironment {
	static $environment;

	if ( ! isset( $environment ) ) {
		$environment = new CurrentEnvironment();
	}

	return $environment;
}

function helsinki_wp_resilient_logger_config(): ResilientLoggerConfig {
	static $config;

	if ( ! isset( $config ) ) {
		$config = new ResilientLoggerConfig();
	}

	return $config;
}

function helsinki_wp_resilient_logger_scheduler(): ResilientLoggerScheduler {
	return new ResilientLoggerScheduler();
}

function helsinki_wp_resilient_logger_wsal_adapter(): WSALAugment {
	return new WSALAugment(
		new HumanReadableDiffer()
	);
}

function helsinki_wp_resilient_logger_db_migrator(): Migrator {
	return new Migrator();
}

function helsinki_wp_resilient_logger_native_log_source(): AbstractLogSource {
	global $wpdb;

	return new ResilientLoggerLogSource(
		new ResilientLoggerData(
			helsinki_wp_resilient_logger_config(),
			$wpdb,
			ResilientLoggerTables::resilient_log($wpdb),
			ResilientLoggerTables::date_time_format()
		),
		helsinki_wp_resilient_logger_config()
	);
}

function helsinki_wp_resilient_logger_wsal_log_source(): AbstractLogSource {
	global $wpdb;

	return new WSALLogSource(
		new WSALData(
			helsinki_wp_resilient_logger_config(),
			$wpdb,
			ResilientLoggerTables::wsal_sync($wpdb),
			ResilientLoggerTables::date_time_format()
		),
		new WSALAlertAdapter(),
		helsinki_wp_resilient_logger_config()
	);
}
