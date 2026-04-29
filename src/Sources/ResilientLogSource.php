<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Sources;

use ResilientLogger\Sources\AbstractLogSource;
use WP\helfi_resilient_logger\Entities\ResilientLogEntity;

class ResilientLogSource implements AbstractLogSource {
    public function __construct(private array $config) {}

    public function create(int $level, mixed $message, array $context = []): ResilientLogSourceEntry {
        $row = ResilientLogEntity::insert(
            $level,
            wp_json_encode($message),
            wp_json_encode($context),
        );

        return new ResilientLogSourceEntry($row, $this->config);
    }

    public function getUnsentEntries(int $chunkSize): \Generator {
        foreach (ResilientLogEntity::get_unsent_entries($chunkSize) as $row) {
            yield new ResilientLogSourceEntry($row, $this->config);
        }
    }

    public function clearSentEntries(int $daysToKeep): void {
        ResilientLogEntity::clear_sent($daysToKeep);
    }
}