<?php

namespace AndyDefer\StorageKit\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record représentant les statistiques du storage JSONL
 */
final class JsonlStorageStatsRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $total_lines_processed,
        public readonly int $processed_files,
        public readonly string $base_path,
        public readonly int $ttl,
    ) {}
}
