<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Sources;

use ResilientLogger\Sources\AbstractLogSource;
use WP\helfi_resilient_logger\ResilientLogger;
use WP\helfi_resilient_logger\Database\WSALSyncEntity;
use WSAL\Entities\Occurrences_Entity;

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
        'PostID'     => 'post',
        'UserID'     => 'user',
        'TermID'     => 'taxonomy_term',
        'CommentID'  => 'comment',
        'MenuID'     => 'menu',
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

    public static function create(int $level, mixed $message, array $context = []): AbstractLogSource {
        throw new \RuntimeException('This source does not support direct instance creation');
    }

    public function getId(): int {
        return (int) $this->row['id'];
    }

    public function isSent(): bool {
        return (bool) ($this->row['is_sent'] ?? false);
    }

    public function markSent(): void {
        WSALSyncEntity::mark_sent($this->getId());
    }

    public static function getUnsentEntries(int $chunkSize): \Generator {
        $sentIds = WSALSyncEntity::get_sent_ids();

        if (empty($sentIds)) {
            $rows = Occurrences_Entity::load_array(
                '1 = 1 ORDER BY id ASC LIMIT %d',
                [$chunkSize]
            );
        } else {
            $placeholders = implode(',', array_fill(0, count($sentIds), '%d'));
            $rows = Occurrences_Entity::load_array(
                "id NOT IN ($placeholders) ORDER BY id ASC LIMIT %d",
                [...$sentIds, $chunkSize]
            );
        }

        if (!empty($rows)) {
            Occurrences_Entity::get_multi_meta_array($rows);

            foreach ($rows as $row) {
                yield new self($row);
            }
        }
    }

    public static function clearSentEntries(int $daysToKeep): void {
        ResilientLogger::getInternalLogger()->info(
            'WSALLogSource does not support clearing old entries'
        );
    }

    /**
     * @return AuditLogDocument
     */
    public function getDocument(): array {
        $meta      = $this->parseMetadata();
        $target    = $this->parseTarget($meta);
        $details   = $this->parseAlertDetails();
        $timestamp = $this->parseDateString((int) $this->row['created_on']);

        return [
            '@timestamp' => $timestamp,
            'audit_event' => [
                'actor' => [
                    'user_id' => (string) ($meta['CurrentID'] ?? '0'),
                    'ip'      => (string) ($meta['ClientIP'] ?? 'unknown'),
                ],
                'date_time'   => $timestamp,
                'operation'   => $details['operation'],
                'origin'      => (string) (self::$config['origin'] ?? 'wordpress'),
                'target'      => $target,
                'environment' => (string) (self::$config['environment'] ?? 'unknown'),
                'message'     => (string) ($this->row['message'] ?? ''),
                'level'       => 200,
                'extra'       => array_merge($meta, [
                    'WSAL_AlertId'   => (int) $this->row['alert_id'],
                    'WSAL_AlertDesc' => $details['description'],
                ]),
            ],
        ];
    }

    // — Parsing ———————————————————————————————————————————————————————————————

    private function parseMetadata(): array {
        return $this->row['meta_values'] ?? [];
    }

    private function parseDateString(int $unixTime): string {
        $timestamp = new \DateTimeImmutable("@{$unixTime}");
        return $timestamp->format(\DateTime::ATOM);
    }

    /**
     * Maps the alert ID to a human-readable operation and description.
     *
     * Generated from https://melapress.com/support/kb/wp-activity-log-list-event-ids/
     * with AI assistance — may not always be correct.
     *
     * @return array{operation: string, description: string}
     */
    private function parseAlertDetails(): array {
        $id = (int) $this->row['alert_id'];

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
            isset($specialCases[$id])          => $specialCases[$id],
            $id >= 1000 && $id <= 1999         => ['READ',    'User Session/Auth Activity'],
            $id >= 2000 && $id <= 2999         => ['UPDATE',  'Content/Post Modification'],
            $id >= 4000 && $id <= 4999         => ['UPDATE',  'User Profile Update'],
            $id >= 5000 && $id <= 5999         => ['UPDATE',  'Plugin/Theme Management'],
            $id >= 6000 && $id <= 6999         => ['UPDATE',  'System/WSAL Settings Change'],
            $id >= 8000 && $id <= 9999         => ['UPDATE',  'Extension/WooCommerce Action'],
            default => (function () use ($id) {
                ResilientLogger::getInternalLogger()->warning(
                    "WSAL Mapper: Unknown Alert ID ({$id})."
                );
                return ['UNKNOWN', "Activity Log Event ({$id})"];
            })(),
        };

        return ['operation' => $details[0], 'description' => $details[1]];
    }

    /**
     * @param array<string, string> $meta
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

        if ($targetId === 'unknown') {
            $targetId = 'alert_' . ($this->row['alert_id'] ?? 'none');
        }

        return [
            'id'      => $targetId,
            'type'    => $targetType,
            'site_id' => $this->row['site_id'] ?? '1',
        ];
    }
}
