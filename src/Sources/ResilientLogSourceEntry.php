<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Sources;

use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Utils\Helpers;
use WP\helfi_resilient_logger\Entities\ResilientLogEntity;

/**
 * @phpstan-import-type AuditLogDocument from ResilientLogger\Types
 */
class ResilientLogSourceEntry implements AbstractLogSourceEntry {
    public function __construct(private array $row, private array $config) {}

    public function getId(): int {
        return (int) $this->row['id'];
    }

    public function isSent(): bool {
        return (bool) ($this->row['is_sent'] ?? false);
    }

    public function markSent(): void {
        if ($this->isSent()) {
            return;
        }

        if (ResilientLogEntity::mark_sent($this->getId())) {
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
        $createdAt = new \DateTimeImmutable($this->row['created_at']);
        $message   = is_array($message) ? json_encode($message) : (string) $message;

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
                'origin'      => (string) ($this->config['origin'] ?? 'wordpress'),
                'target'      => Helpers::valueAsArray($target),
                'environment' => (string) ($this->config['environment'] ?? 'unknown'),
                'message'     => $message,
                'level'       => $level,
                'extra'       => $context,
            ],
        ];
    }
}