<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Sources;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use WP\helfi_resilient_logger\ResilientLogger;
use WP\helfi_resilient_logger\Entities\WSALSyncEntity;
use WSAL\Entities\Occurrences_Entity;

class WSALLogSource implements AbstractLogSource {
    private function __construct(private array $config) {}

    public function create(int $level, mixed $message, array $context = []): AbstractLogSourceEntry {
        throw new \RuntimeException('This source does not support direct instance creation');
    }

    public function getUnsentEntries(int $chunkSize): \Generator {
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
                yield new WSALLogSourceEntry($row, $this->config);
            }
        }
    }

    public function clearSentEntries(int $daysToKeep): void {
        ResilientLogger::getInternalLogger()->info(
            'WSALLogSource does not support clearing old entries'
        );
    }
}