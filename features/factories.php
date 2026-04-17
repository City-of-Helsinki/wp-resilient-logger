<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP\helfi_resilient_logger\ResilientLoggerAdapter;
use WP\helfi_resilient_logger\ResilientLoggerConfig;
use WP\helfi_resilient_logger\Cron\ResilientLoggerScheduler;
use WP\helfi_resilient_logger\Cron\ResilientLoggerSchedule;
use WP\helfi_resilient_logger\Helpers\CurrentEnvironment;
use WP\helfi_resilient_logger\Helpers\WSALAugment;
use WP\helfi_resilient_logger\Database\Migrator;
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
