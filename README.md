# WordPress Resilient Logger

WordPress-compatible implementation of [`city-of-helsinki/php-resilient-logger`](https://github.com/City-of-Helsinki/php-resilient-logger).

Provides reliable delivery of audit and operational events from WordPress to external systems. Supports PSR-3 logging, but is primarily designed as a general event transport mechanism with retry and fallback.

---

## Features

- WordPress runtime integration  
- Reliable event delivery with retry and fallback  
- Audit and operational event logging  
- PSR-3 compatible  
- Provides default log sources (WSAL integration and a Monolog-compatible handler) and allows developers to add custom sources. Each source stores entries in its own table but submits to external targets in the same way.  
- Internal plugin logging (outputs to PHP error log and WP-CLI by default)  

---

## Installation

### Via Composer

```bash
composer require city-of-helsinki/wp-resilient-logger
```

### As a WordPress plugin

1. Upload the plugin folder to `wp-content/plugins/`  
2. Activate via WordPress admin  

> **Note:** If the WP Security Audit Log plugin (WSAL) is installed after this plugin, you must deactivate and reactivate this plugin to correctly create the required SQL tables.

---

## Configuration

Example configuration:

```php
/** Main resilient logger settings */
define('RESILIENT_LOGGER_SETTINGS', [
  'sources' => [
    [
      'class' => 'WP\helfi_resilient_logger\Sources\WSALLogSource',
    ],
  ],
  'targets' => [
    [
      "class" => 'ResilientLogger\Targets\ElasticsearchLogTarget',
      "es_host" => 'host.docker.internal',
      "es_port" => 9200,
      "es_scheme" => 'http',
      "es_username" => 'username',
      "es_password" => 'password',
      "es_index" => 'index-name'
    ]
  ],
  'origin'                 => 'helsinki-wp-dev',
  'store_old_entries_days' => 30,
  'batch_limit'            => 5000,
  'chunk_size'             => 500,
  'submit_unsent_entries'  => true,
  'clear_sent_entries'     => true,
]);

/** Define this and set to true if native WP cron is to be used */
define('RESILIENT_LOGGER_USE_WP_CRON', true);
```

Current environment is determined with `wp_get_environment_type()`.

---

## WP-CLI

The following commands are available and intended to run via external cron:

```bash
wp resilient-logger submit_unsent_entries    # recommended every 15 minutes
wp resilient-logger clear_sent_entries       # recommended once per month
```

---

## Integration

- Integrates with [WP Security Audit Log](https://wordpress.org/plugins/wp-security-audit-log/) by generating external sync/meta tables  
- Supports multiple sources and targets via configuration  

---

## Relationship to `php-resilient-logger`

| Layer | Responsibility |
|-------|----------------|
| `php-resilient-logger` | Abstract transport and resilience primitives |
| `wp-resilient-logger`  | WordPress-specific integration and concrete implementations |

---

## License

MIT — see [LICENSE](./LICENSE)
