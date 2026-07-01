<?php

namespace AndyDefer\StorageKit\Enums;

enum CacheDriver: string
{
    case FILES = 'Files';
    case SQLITE = 'Sqlite';
}
