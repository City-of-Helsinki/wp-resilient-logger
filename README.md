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

---

## Configuration

Example configuration:

```php
/** Main resilient logger settings */
define('RESILIENT_LOGGER_SETTINGS', [
  'sources' => [
    [
      'factory' => 'helsinki_wp_resilient_logger_wsal_log_source',
    ],
  ],
  'targets' => [
    [
      'class' => 'ResilientLogger\Targets\ElasticsearchLogTarget',
      'es_url' => getenv('AUDIT_LOG_ES_URL') ?: 'http://host.docker.internal:9200',
      'es_username' => getenv('AUDIT_LOG_ES_USERNAME') ?: '',
      'es_password' => getenv('AUDIT_LOG_ES_PASSWORD') ?: '',
      'es_index' => getenv('AUDIT_LOG_ES_INDEX') ?: 'index-name',
    ]
  ],
  'origin'                 => getenv('AUDIT_LOG_ORIGIN') ?: 'my-app',
  'store_old_entries_days' => 30,
  'batch_limit'            => 5000,
  'chunk_size'             => 500,
  'submit_unsent_entries'  => true,
  'clear_sent_entries'     => true,
]);

/** Define this and set to true if native WP cron is to be used */
define( 'RESILIENT_LOGGER_USE_WP_CRON', true );
```

Current environment is determined with `wp_get_environment_type()`.

### Environment variables

The environment variable names below are the standard names used by the infrastructure configuration for this library. Unless your environment explicitly requires different names, use these names as-is.

**Elasticsearch:**

* `AUDIT_LOG_ES_URL` — Elasticsearch endpoint URL
* `AUDIT_LOG_ES_USERNAME` — Elasticsearch username
* `AUDIT_LOG_ES_PASSWORD` — Elasticsearch password
* `AUDIT_LOG_ES_INDEX` — Elasticsearch index

**General Resilient Logger configuration:**

* `AUDIT_LOG_ENV` — environment identifier
* `AUDIT_LOG_ORIGIN` — identifies the application or system producing the logs

The Elasticsearch endpoint can also be configured using the individual endpoint components supported by `php-resilient-logger`: `es_scheme`, `es_host`, and `es_port`. See the [`php-resilient-logger`](https://github.com/City-of-Helsinki/php-resilient-logger#example-target-elasticsearch)[ configuration documentation](https://github.com/City-of-Helsinki/php-resilient-logger#example-target-elasticsearch) for details.

---

## WP-CLI

The following commands are available and intended to run via external cron:

```bash
wp resilient-logger entries submit    # recommended every 15 minutes
wp resilient-logger entries clear       # recommended once per month
```

When using `RESILIENT_LOGGER_USE_WP_CRON` the above commands are executed on `wp cron event run --due-now` command.

---

## Integration

- Integrates with [WP Security Audit Log](https://wordpress.org/plugins/wp-security-audit-log/) by generating external sync/meta tables  
- Supports multiple sources and targets via configuration

### WP Security Audit Log

WP Resilient Logger overrides selected WSAL settings with predefined values. The overrides are defined in `Sources\WSAL\Settings\WSALForcedSettings`.

By default all but defined WSAL events are logged. The excluded events are defined in `Sources\WSAL\Settings\WSALDisabledAlerts`. Site administrators cannot enable or disable logged events in the site dashboard.

Available configuration constants for `wp-config.php`

```php
// Hide WP Activity Log > Enable / Disable Events menu page.
define( 'RESILIENT_LOGGER_WSAL_DISABLE_EVENTS_VIEW', true );

// Hide WP Activity Log > Settings menu page and disable settings editing
define( 'RESILIENT_LOGGER_WSAL_DISALLOW_EDIT_SETTINGS', true );
```

## Event filters

WP Resilient Logger provides filters to prevent logging of certain events. You can for example add any of the following filters to a `mu-plugin`.

Replace `{role}` with role name, e.g. `administrator`.

```php
// Login
add_filter( 'helsinki_wp_resilient_logger_log_{role}_login', '__return_false' );
add_filter( 'helsinki_wp_resilient_logger_log_login', '__return_false' );

// Logout
add_filter( 'helsinki_wp_resilient_logger_log_{role}_logout', '__return_false' );
add_filter( 'helsinki_wp_resilient_logger_log_logout', '__return_false' );
```

---

## Relationship to `php-resilient-logger`

| Layer | Responsibility |
|-------|----------------|
| `php-resilient-logger` | Abstract transport and resilience primitives |
| `wp-resilient-logger`  | WordPress-specific integration and concrete implementations |

---

## License

MIT — see [LICENSE](./LICENSE)
