<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Sources;

use ResilientLogger\Sources\AbstractLogSource;
use WP\helfi_resilient_logger\ResilientLogger;

/**
 * @phpstan-import-type AuditLogDocument from ResilientLogger\Types
 */
class WSALLogSource implements AbstractLogSource {
  private array $row;
  private static array $config = [];
  
  /**
   * Map of WSAL metadata keys to their respective resource types.
   */
  public const WSAL_TARGET_MAP = [
    // Object-specific targets
    'PostID'     => 'post',
    'UserID'     => 'user',
    'TermID'     => 'taxonomy_term',
    'CommentID'  => 'comment',
    'MenuID'     => 'menu',
    
    // System & Configuration targets
    'OptionName' => 'setting',
    'RoleName'   => 'user_role',
    'PluginFile' => 'plugin',
    'ThemeName'  => 'theme',
    'FileName'   => 'file',
  ];

  private function __construct(array $row) {
    $this->row = $row;
  }

  public static function configure(mixed $config): void {
    self::$config = (array) $config;
  }

  /**
   * Database schema definition for the external sync.
   */
  public static function install(): void {
    global $wpdb;

    $syncTable = self::getSyncTable();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $syncTable (
        occurrence_id bigint(20) NOT NULL,
        is_sent tinyint(1) DEFAULT 0 NOT NULL,
        sent_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (occurrence_id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
  }

  public static function isWsalInstalled(): bool {
    // Check if the main WSAL class exists
    return class_exists('WpSecurityAuditLog');
  }

  private static function getWsalTable(): string {
    global $wpdb;
    return $wpdb->prefix . 'wsal_occurrences';
  }

  private static function getMetaTable(): string {
    global $wpdb;
    return $wpdb->prefix . 'wsal_metadata';
  }

  private static function getSyncTable(): string {
    global $wpdb;
    return $wpdb->prefix . 'helfi_wsal_sync';
  }

  public function getId(): int {
    return (int) $this->row['id'];
  }

  public function isSent(): bool {
    return (bool) ($this->row['is_sent'] ?? false);
  }

  public static function create(int $level, mixed $message, array $context = []): AbstractLogSource {
    throw new \RuntimeException('This source does not support direct instance creation');
  }

  public function markSent(): void {
    global $wpdb;
    
    $id = (int) $this->row['id'];
    $now = current_time('mysql', true);
    $syncTable = self::getSyncTable();

    $query = $wpdb->prepare(
      "INSERT INTO " . $syncTable . " (occurrence_id, is_sent, sent_at) 
        VALUES (%d, 1, %s) 
        ON DUPLICATE KEY UPDATE is_sent = 1, sent_at = %s",
      $id,
      $now,
      $now
    );

    $wpdb->query($query);
  }

  /**
   * @return AuditLogDocument
   */
  public function getDocument(): array {
    $meta   = $this->parseMetadata();
    $target = $this->parseTarget($meta);
    $details = $this->parseAlertDetails();
    $timestamp = $this->parseDateString((int) $this->row['created_on']);

    return [
      "@timestamp" => $timestamp,
      "audit_event" => [
        "actor" => [
          "user_id" => (string) ($meta['CurrentID'] ?? '0'),
          "ip"      => (string) ($meta['ClientIP'] ?? 'unknown'),
        ],
        "date_time"   => $timestamp,
        "operation"   => $details["operation"],
        "origin"      => (string) (self::$config['origin'] ?? 'wordpress'),
        "target"      => $target,
        "environment" => (string) (self::$config['environment'] ?? 'unknown'),
        "message"     => (string) ($this->row['message'] ?? ''),
        "level"       => 200,
        "extra"       => array_merge($meta, [
          "wsal_alert_id"   => (int) $this->row['alert_id'],
          "wsal_alert_desc" => $details["description"],
        ]),
      ],
    ];
  }

  public static function getUnsentEntries(int $chunkSize): \Generator {
    global $wpdb;

    $wsalTable = self::getWsalTable();
    $syncTable = self::getSyncTable();
    $metaTable = self::getMetaTable();

    $query = $wpdb->prepare(
      "SELECT 
          wt.*, 
          GROUP_CONCAT(CONCAT(mt.name, ':', mt.value) SEPARATOR '||') as metadata
        FROM " . $wsalTable . " AS wt
        LEFT JOIN " . $syncTable . " AS tt ON wt.id = tt.occurrence_id
        LEFT JOIN " . $metaTable . " AS mt ON wt.id = mt.occurrence_id
        WHERE COALESCE(tt.is_sent, 0) = 0
        GROUP BY wt.id
        ORDER BY wt.id ASC 
        LIMIT %d",
      $chunkSize
    );

    $rows = $wpdb->get_results($query, ARRAY_A);

    if ($rows) {
      foreach ($rows as $row) {
        yield new self($row);
      }
    }
  }

  public static function clearSentEntries(int $daysToKeep): void {
    $logger = ResilientLogger::getInternalLogger();
    $logger->info("WSALLogSource does not support clearing old entries");
  }

  /**
   * Parses the GROUP_CONCAT metadata string into an associative array.
   */
  private function parseMetadata(): array {
    $meta = [];
    if (empty($this->row['metadata'])) {
      return $meta;
    }

    foreach (explode('||', $this->row['metadata']) as $pair) {
      $parts = explode(':', $pair, 2);
      $key   = $parts[0] ?? null;
      $value = $parts[1] ?? '';

      if ($key) {
        $meta[$key] = $value;
      }
    }
    return $meta;
  }

  private function parseDateString(int $unixTime): string {
    $timestamp = new \DateTimeImmutable("@{$unixTime}");
    $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    return $timestamp->format(\DateTime::ATOM);
  }

  /**
   * Maps the Alert ID to a human readable details.
   * This function is generated from https://melapress.com/support/kb/wp-activity-log-list-event-ids/
   * with a help of a AI and might not always be correct. 
   * 
   * The information is stored elsewhere, this is only used to store 
   * human readable CRUD-like operation and simple description.
   * 
   * @return array{operation: string, description: string}
   */
  private function parseAlertDetails(): array {
    $id = (int) $this->row['alert_id'];

    // Exact matches for non-range based alert ids.
    $specialCases = [
        // CREATES
        2000 => ['CREATE', 'Created a new post'],
        2001 => ['CREATE', 'Published a post'],
        2023 => ['CREATE', 'Created a new category'],
        2042 => ['CREATE', 'Added a new widget'],
        2078 => ['CREATE', 'Created a menu'],
        2121 => ['CREATE', 'Created a new tag'],
        4000 => ['CREATE', 'New user created'],
        4001 => ['CREATE', 'User created a new user'],
        4012 => ['CREATE', 'Created a new network user'],
        5000 => ['CREATE', 'Installed a plugin'],
        5005 => ['CREATE', 'Installed a theme'],
        6314 => ['CREATE', 'WSAL: Custom notification added'],
        6317 => ['CREATE', 'WSAL: SMS notification added'],

        // READS
        1000 => ['READ', 'User successfully logged in'],
        1008 => ['READ', 'Switched to another user'],
        2100 => ['READ', 'Opened a post in editor'],
        2101 => ['READ', 'Viewed a post'],
        4014 => ['READ', 'Opened a user profile page'],
        6069 => ['READ', 'Cron task executed'],

        // DELETES
        2008 => ['DELETE', 'Permanently deleted a post'],
        2011 => ['DELETE', 'Deleted a file'],
        2012 => ['DELETE', 'Moved a post to trash'],
        2024 => ['DELETE', 'Deleted a category'],
        2044 => ['DELETE', 'Deleted a widget'],
        2081 => ['DELETE', 'Deleted a menu'],
        2096 => ['DELETE', 'Moved a comment to trash'],
        2098 => ['DELETE', 'Permanently deleted a comment'],
        2122 => ['DELETE', 'Deleted a tag'],
        4007 => ['DELETE', 'Deleted a user'],
        5003 => ['DELETE', 'Uninstalled a plugin'],
        5007 => ['DELETE', 'Deleted a theme'],
        6030 => ['DELETE', 'File deleted from website'],
        6034 => ['DELETE', 'Activity log purged'],
        6316 => ['DELETE', 'WSAL: Custom notification deleted'],
        6319 => ['DELETE', 'WSAL: SMS notification deleted'],
    ];

    $details = match (true) {
        isset($specialCases[$id]) => $specialCases[$id],
        ($id >= 1000 && $id <= 1999) => ['READ',   'User Session/Auth Activity'],
        ($id >= 2000 && $id <= 2999) => ['UPDATE', 'Content/Post Modification'],
        ($id >= 4000 && $id <= 4999) => ['UPDATE', 'User Profile Update'],
        ($id >= 5000 && $id <= 5999) => ['UPDATE', 'Plugin/Theme Management'],
        ($id >= 6000 && $id <= 6999) => ['UPDATE', 'System/WSAL Settings Change'], // Includes 63xx
        ($id >= 8000 && $id <= 9999) => ['UPDATE', 'Extension/WooCommerce Action'],
        
        default => (function() use ($id) {
          $logger = ResilientLogger::getInternalLogger();;
          $logger->warning("WSAL Mapper: Unknown Alert ID ($id).");
            return ['UNKNOWN', "Activity Log Event ($id)"];
        })(),
    };

    return ["operation" => $details[0], "description" => $details[1]];
}

  /**
   * Determines target ID and type based on metadata.
   *
   * @param array<string, string> $meta Parsed metadata from WSAL.
   * @return array{id: string|int, type: string, site_id: int|string}
   */
  private function parseTarget(array $meta): array {
    $targetId   = 'unknown';
    $targetType = 'unmapped_target';

    foreach (self::WSAL_TARGET_MAP as $metaKey => $typeHint) {
      if (isset($meta[$metaKey])) {
        $targetId   = $meta[$metaKey];
        $targetType = $typeHint;
        break;
      }
    }

    // If we couldn't find a specific target ID in the metadata, 
    // we use the alert_id as the ID to help identify the event type.
    if ($targetId === 'unknown') {
      $targetId = 'alert_' . ($this->row['alert_id'] ?? 'none');
    }

    return [
      "id"      => $targetId,
      "type"    => $targetType,
      "site_id" => $this->row['site_id'] ?? '1',
    ];
  }
}