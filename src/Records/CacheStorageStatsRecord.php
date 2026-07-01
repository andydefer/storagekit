<?php

namespace AndyDefer\StorageKit\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\StorageKit\Enums\CacheDriver;

final class CacheStorageStatsRecord extends AbstractRecord
{
    public function __construct(
        public readonly CacheDriver $driver,
        public readonly int $hits,
        public readonly int $misses,
        public readonly int $sets,
        public readonly int $deletes,
        public readonly int $items_count,
    ) {}
}
