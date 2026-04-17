<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerAdapter;
use CityOfHelsinki\WP\ResilientLogger\ResilientLoggerConfig;
use CityOfHelsinki\WP\ResilientLogger\Cron\ResilientLoggerScheduler;
use CityOfHelsinki\WP\ResilientLogger\Cron\ResilientLoggerSchedule;
use CityOfHelsinki\WP\ResilientLogger\Helpers\CurrentEnvironment;
use CityOfHelsinki\WP\ResilientLogger\Helpers\WSALAugment;
use CityOfHelsinki\WP\ResilientLogger\Database\Migrator;
use ResilientLogger\Utils\HumanReadableDiffer;

function helsinki_wp_resilient_logger(): ResilientLoggerAdapter {
	return new ResilientLoggerAdapter(
		helsinki_wp_resilient_logger_config(),
	);
}

function helsinki_wp_resilient_logger_environment(): CurrentEnvironment {
	return new CurrentEnvironment();
}

function helsinki_wp_resilient_logger_config(): ResilientLoggerConfig {
	return new ResilientLoggerConfig();
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
