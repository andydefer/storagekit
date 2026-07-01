<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Enums;

/**
 * Available storage system implementations.
 *
 * Each system provides different persistence and performance characteristics.
 */
enum StorageSystem: string
{
    /**
     * In-memory storage using PHP arrays.
     * Fastest, but data is lost when script ends.
     * Ideal for testing and short-lived data.
     */
    case MEMORY = 'memory';

    /**
     * Persistent storage using JSON Lines (JSONL) format.
     * Data is stored on disk and survives script execution.
     * Good for production use with moderate performance needs.
     */
    case JSONL = 'jsonl';

    /**
     * Cache-based storage using PhpFastCache.
     * Supports multiple backends (Files, Sqlite) with TTL support.
     * Best for high-performance production use.
     */
    case CACHE = 'cache';

    /**
     * Session-based storage using PHP's $_SESSION superglobal.
     * Data is stored in the user's session and persists across requests.
     * Requires session_start() to be called before usage.
     */
    case SESSION = 'session';

    /**
     * Cookie-based storage using PHP's setcookie() function.
     * Data is stored in the user's browser cookies.
     * Data is limited by cookie size (4KB) and number of cookies.
     */
    case COOKIE = 'cookie';

    /**
     * SQLite-based persistent storage.
     * Uses SQLite database for reliable, ACID-compliant storage.
     * Good for moderate-sized datasets requiring persistence.
     */
    case SQLITE = 'sqlite';
}
