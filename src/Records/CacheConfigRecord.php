<?php

namespace AndyDefer\StorageKit\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class CacheConfigRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $path = null,
    ) {}
}
