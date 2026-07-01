<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Statistics record for SQLite storage.
 *
 * Provides metrics about SQLite database operations and storage usage.
 *
 * @example
 * $stats = $storage->getStats();
 * echo "Items: {$stats->total_items}\n";
 * echo "Size: {$stats->database_size} bytes\n";
 */
final class SqliteStorageStatsRecord extends AbstractRecord
{
    public function __construct(
        /** Total number of key-value pairs stored in the database. */
        public readonly int $total_items,

        /** Total size of the database file in bytes (0 for in-memory). */
        public readonly int $database_size,

        /** Path to the database file or ':memory:' for in-memory. */
        public readonly string $database_path,

        /** Name of the table used for key-value storage. */
        public readonly string $table_name,

        /** Whether the database is in-memory. */
        public readonly bool $is_memory,

        /** Number of active transactions (0 if none). */
        public readonly int $active_transactions,

        /** Number of times the database has been written to. */
        public readonly int $write_count,

        /** Number of times the database has been read from. */
        public readonly int $read_count,

        /** Page size of the database in bytes. */
        public readonly int $page_size,

        /** Total number of pages in the database. */
        public readonly int $total_pages,
    ) {}
}
