<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Sources;

use ResilientLogger\Sources\AbstractLogSourceEntry;
use WP\helfi_resilient_logger\ResilientLogger;
use WP\helfi_resilient_logger\Entities\WSALSyncEntity;
use WP\helfi_resilient_logger\Helpers\WSALLookup;

/**
 * @phpstan-import-type AuditLogDocument from ResilientLogger\Types
 */
class WSALLogSourceEntry implements AbstractLogSourceEntry {
    public function __construct(private array $row, private array $config) {}

    public function getId(): int {
        return (int) $this->row['id'];
    }

    public function isSent(): bool {
        return (bool) ($this->row['is_sent'] ?? false);
    }

    public function markSent(): void {
        WSALSyncEntity::mark_sent($this->getId());
    }

    /**
     * @return AuditLogDocument
     */
    public function getDocument(): array {
        $meta      = $this->parseMetadata();
        $target    = WSALLookup::parseAlertTarget($meta, $this->row);
        $details   = WSALLookup::parseAlertDetails($this->row);
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
                'origin'      => (string) ($this->config['origin'] ?? 'wordpress'),
                'target'      => $target,
                'environment' => (string) ($this->config['environment'] ?? 'unknown'),
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
}