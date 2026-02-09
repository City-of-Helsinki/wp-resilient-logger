<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Sources;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Utils\Helpers;

/**
 * @phpstan-import-type AuditLogDocument from ResilientLogger\Types
 */
class ResilientLogSource implements AbstractLogSource {
  private static array $config = [];

  private array $row;

  private function __construct(array $row) {
    $this->row = $row;
  }

  public function getId(): int {
    return (int) $this->row['id'];
  }

  public function isSent(): bool {
    return (bool) ($this->row['is_sent'] ?? false);
  }

  public function markSent(): void {
    global $wpdb;

    if ($this->isSent()) {
      return;
    }

    $result = $wpdb->update(
      self::getTable(),
      ['is_sent' => 1],
      ['id' => $this->getId()],
      ['%d'],
      ['%d']
    );

    if (false !== $result) {
      $this->row['is_sent'] = 1;
    }
  }

  /**
   * @return AuditLogDocument
   */
  public function getDocument(): array {
    $level     = (int) $this->row['level'];
    $message   = json_decode($this->row['message'], true);
    $context   = json_decode($this->row['context'], true) ?: [];
    
    // Ensure we return a DateTimeImmutable object
    $createdAt = new \DateTimeImmutable($this->row['created_at']);
    $message = is_array($message) ? json_encode($message) : (string) $message;

    $actor     = $context['actor']     ?? 'unknown';
    $operation = $context['operation'] ?? 'MANUAL';
    $target    = $context['target']    ?? 'unknown';

    unset($context['actor'], $context['operation'], $context['target']);

    return [
      '@timestamp' => $createdAt,
      'audit_event' => [
        'actor'       => Helpers::valueAsArray($actor),
        'date_time'   => $createdAt,
        'operation'   => $operation,
        'origin'      => (string) (self::$config['origin'] ?? 'wordpress'),
        'target'      => Helpers::valueAsArray($target),
        'environment' => (string) (self::$config['environment'] ?? 'unknown'),
        'message'     => $message,
        'level'       => $level,
        'extra'       => $context,
      ],
    ];
  }

  public static function configure(mixed $config): void {
    self::$config = (array) $config;
  }

  public static function install(): void {
    global $wpdb;

    $table   = self::getTable();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level int(11) NOT NULL,
            message longtext NOT NULL,
            context longtext,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            is_sent tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY is_sent_created_at (is_sent, created_at)
        ) $charset;";

    require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
    \dbDelta($sql);
  }

  public static function create(int $level, mixed $message, array $context = []): self {
    global $wpdb;

    $inserted = $wpdb->insert(
      self::getTable(),
      [
        'level'      => $level,
        'message'    => \wp_json_encode($message),
        'context'    => \wp_json_encode($context),
        'created_at' => \current_time('mysql', true),
        'is_sent'    => 0,
      ],
      ['%d', '%s', '%s', '%s', '%d']
    );

    if (false === $inserted) {
      throw new \RuntimeException("Failed to insert log into WordPress database.");
    }

    $id = (int) $wpdb->insert_id;
    $table = self::getTable();
    $query = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
    $row = $wpdb->get_row($query, ARRAY_A);

    return new self($row);
  }

  public static function getUnsentEntries(int $chunkSize): \Generator {
    global $wpdb;

    $table = self::getTable();
    $query = $wpdb->prepare(
      "SELECT * FROM $table WHERE is_sent = 0 ORDER BY created_at ASC LIMIT %d",
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
    global $wpdb;

    $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
    $table = self::getTable();
    $query = $wpdb->prepare(
      "DELETE FROM $table WHERE is_sent = 1 AND created_at <= %s",
      $cutoff
    );

    $wpdb->query($query);
  }

  private static function getTable(): string {
    global $wpdb;
    return $wpdb->prefix . 'helfi_resilient_log';
  }
}