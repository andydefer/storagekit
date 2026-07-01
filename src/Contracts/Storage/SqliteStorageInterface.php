<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Contracts\Storage;

use AndyDefer\StorageKit\Records\SqliteStorageStatsRecord;
use SQLite3;

/**
 * Extended storage interface for SQLite-based persistence.
 *
 * Provides SQLite-specific methods for connection management, transaction control,
 * and storage statistics. This interface extends StorageInterface with additional
 * functionality for database operations.
 *
 * @example
 * $storage = new SqliteStorage('data.db');
 * $storage->beginTransaction();
 * $storage->set('key1', 'value1');
 * $storage->set('key2', 'value2');
 * $storage->commit();
 */
interface SqliteStorageInterface extends StorageInterface
{
    /**
     * Gets the path to the SQLite database file.
     *
     * @return string The database file path
     */
    public function getDatabasePath(): string;

    /**
     * Gets the table name used for key-value storage.
     *
     * @return string The table name
     */
    public function getTableName(): string;

    /**
     * Checks if the storage is using an in-memory database.
     *
     * @return bool True if using :memory: database, false otherwise
     */
    public function isMemoryDatabase(): bool;

    /**
     * Begins a transaction for batch operations.
     *
     * All subsequent write operations (set, delete, setMultiple, deleteMultiple)
     * will be performed within the transaction until commit() or rollback() is called.
     *
     * @return bool True if transaction was started successfully
     */
    public function beginTransaction(): bool;

    /**
     * Commits the current transaction.
     *
     * Persists all changes made within the transaction to the database.
     *
     * @return bool True if transaction was committed successfully
     */
    public function commit(): bool;

    /**
     * Rolls back the current transaction.
     *
     * Discards all changes made within the transaction.
     *
     * @return bool True if transaction was rolled back successfully
     */
    public function rollback(): bool;

    /**
     * Checks if a transaction is currently active.
     *
     * @return bool True if a transaction is in progress, false otherwise
     */
    public function inTransaction(): bool;

    /**
     * Gets the number of items stored in the database.
     *
     * @return int Total number of key-value pairs
     */
    public function count(): int;

    /**
     * Gets the total size of the database file in bytes.
     *
     * Returns 0 for in-memory databases.
     *
     * @return int Database file size in bytes, or 0 for in-memory
     */
    public function getDatabaseSize(): int;

    /**
     * Gets storage statistics.
     *
     * @return SqliteStorageStatsRecord Statistics record containing storage metrics
     */
    public function getStats(): SqliteStorageStatsRecord;

    /**
     * Optimizes the database by running VACUUM command.
     *
     * This reclaims unused space and defragments the database file.
     * Note: This requires exclusive access to the database.
     *
     * @return bool True if vacuum was executed successfully
     */
    public function vacuum(): bool;

    /**
     * Gets the underlying SQLite3 connection instance.
     *
     * Provides direct access for advanced operations.
     *
     * @return SQLite3 The SQLite3 connection
     */
    public function getConnection(): SQLite3;

    /**
     * Closes the database connection.
     *
     * @return bool True if connection was closed successfully
     */
    public function close(): bool;
}
