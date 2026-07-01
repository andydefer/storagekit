<?php

namespace AndyDefer\StorageKit\Enums;

enum StorageSystem: string
{
    case MEMORY = 'memory';
    case JSONL = 'jsonl';
    case CACHE = 'cache';
}
