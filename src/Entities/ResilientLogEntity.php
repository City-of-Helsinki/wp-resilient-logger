<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Entities;

class ResilientLogEntity {

    private static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'helfi_resilient_log';
    }

    public static function install(): void {
        global $wpdb;

        $table   = self::get_table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level int(11) NOT NULL,
            message longtext NOT NULL,
            context longtext,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            is_sent tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY is_sent_created_at (is_sent, created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function insert(int $level, string $message, string $context): array {
        global $wpdb;

        $inserted = $wpdb->insert(
            self::get_table_name(),
            [
                'level'      => $level,
                'message'    => $message,
                'context'    => $context,
                'created_at' => current_time('mysql', true),
                'is_sent'    => 0,
            ],
            ['%d', '%s', '%s', '%s', '%d']
        );

        if (false === $inserted) {
            throw new \RuntimeException('Failed to insert log into WordPress database.');
        }

        return self::find_by_id($wpdb->insert_id);
    }

    public static function find_by_id(int $id): array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . self::get_table_name() . ' WHERE id = %d', $id),
            ARRAY_A
        );

        if ($row === null) {
            throw new \RuntimeException("Log entry {$id} not found.");
        }

        return $row;
    }

    public static function get_unsent_entries(int $limit): array {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . self::get_table_name() . ' WHERE is_sent = 0 ORDER BY created_at ASC LIMIT %d',
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function mark_sent(int $id): bool {
        global $wpdb;

        return false !== $wpdb->update(
            self::get_table_name(),
            ['is_sent' => 1],
            ['id'      => $id],
            ['%d'],
            ['%d']
        );
    }

    public static function clear_sent(int $daysToKeep): void {
        global $wpdb;

        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . self::get_table_name() . ' WHERE is_sent = 1 AND created_at <= %s',
                $cutoff
            )
        );
    }
}