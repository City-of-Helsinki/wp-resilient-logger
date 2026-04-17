<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Database;

class WSALSyncEntity
{

    private static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'helfi_wsal_sync';
    }

    public static function install(): void {
        global $wpdb;

        $table   = self::get_table_name();
        $collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            occurrence_id bigint(20) NOT NULL,
            is_sent tinyint(1) NOT NULL DEFAULT 0,
            sent_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (occurrence_id)
        ) {$collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function get_sent_ids(): array {
        global $wpdb;

        return array_map(
            'intval',
            $wpdb->get_col(
                'SELECT occurrence_id FROM ' . self::get_table_name() . ' WHERE is_sent = 1'
            )
        );
    }

    public static function mark_sent(int $occurrence_id): void {
        global $wpdb;

        $wpdb->replace(
            self::get_table_name(),
            [
                'occurrence_id' => $occurrence_id,
                'is_sent'       => 1,
                'sent_at'       => current_time('mysql', true),
            ],
            ['%d', '%d', '%s']
        );
    }
}
