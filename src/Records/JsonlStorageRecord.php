<?php

namespace AndyDefer\StorageKit\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record pour stocker les données dans JSONL
 */
final class JsonlStorageRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?string $context = null,
        public readonly ?string $expires_at = null,
    ) {}
}
