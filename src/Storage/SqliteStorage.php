<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Storage;

use AndyDefer\StorageKit\Contracts\Storage\SqliteStorageInterface;
use AndyDefer\StorageKit\Records\SqliteStorageStatsRecord;
use SQLite3;

/**
 * SQLite storage adapter implementing StorageInterface.
 *
 * Provides persistent key-value storage using SQLite database.
 * Supports transactions, batch operations, and storage statistics.
 *
 * @example
 * // Basic usage
 * $storage = new SqliteStorage('/path/to/data.db');
 * $storage->set('user_123', ['name' => 'John']);
 * $value = $storage->get('user_123');
 *
 * // With transactions
 * $storage->beginTransaction();
 * $storage->set('key1', 'value1');
 * $storage->set('key2', 'value2');
 * $storage->commit();
 *
 * // Get statistics
 * $stats = $storage->getStats();
 * echo "Items: {$stats->total_items}\n";
 */
final class SqliteStorage implements SqliteStorageInterface
{
    private SQLite3 $db;

    private string $databasePath;

    private string $table;

    private bool $isMemory;

    private bool $inTransaction = false;

    private int $transactionCount = 0;

    private int $writeCount = 0;

    private int $readCount = 0;

    /**
     * @param  string  $database  Path to SQLite database file (use ':memory:' for in-memory)
     * @param  string  $table  Table name for key-value storage (default: 'storage_kv')
     */
    public function __construct(
        string $database = ':memory:',
        string $table = 'storage_kv'
    ) {
        $this->databasePath = $database;
        $this->table = $table;
        $this->isMemory = $database === ':memory:';

        // Ensure directory exists for persistent database
        if (! $this->isMemory) {
            $directory = dirname($database);
            if (! is_dir($directory)) {
                if (! mkdir($directory, 0755, true)) {
                    throw new \RuntimeException(sprintf(
                        'Unable to create directory "%s" for SQLite database',
                        $directory
                    ));
                }
            }
        }

        $this->db = new SQLite3($database);
        $this->db->enableExceptions(true);

        $this->initializeTable();
    }

    /**
     * Creates the storage table if it doesn't exist.
     */
    private function initializeTable(): void
    {
        $query = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )',
            $this->escapeIdentifier($this->table)
        );

        $this->db->exec($query);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->readCount++;

        $stmt = $this->db->prepare(sprintf(
            'SELECT value FROM %s WHERE key = :key',
            $this->escapeIdentifier($this->table)
        ));

        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();

        if ($result === false) {
            return $default;
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row === false) {
            return $default;
        }

        return unserialize($row['value']);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->writeCount++;

        $serialized = serialize($value);

        $query = sprintf(
            'INSERT OR REPLACE INTO %s (key, value, updated_at)
             VALUES (:key, :value, CURRENT_TIMESTAMP)',
            $this->escapeIdentifier($this->table)
        );

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $serialized, SQLITE3_TEXT);
        $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->writeCount++;

        $stmt = $this->db->prepare(sprintf(
            'DELETE FROM %s WHERE key = :key',
            $this->escapeIdentifier($this->table)
        ));

        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();

        if ($result === false) {
            return false;
        }

        return $this->db->changes() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        $this->readCount++;

        $stmt = $this->db->prepare(sprintf(
            'SELECT 1 FROM %s WHERE key = :key LIMIT 1',
            $this->escapeIdentifier($this->table)
        ));

        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();

        if ($result === false) {
            return false;
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);

        return $row !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $this->readCount++;

        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $query = sprintf(
            'SELECT key, value FROM %s WHERE key IN (%s)',
            $this->escapeIdentifier($this->table),
            $placeholders
        );

        $stmt = $this->db->prepare($query);

        foreach ($keys as $index => $key) {
            $stmt->bindValue($index + 1, $key, SQLITE3_TEXT);
        }

        $result = $stmt->execute();

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = null;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $values[$row['key']] = unserialize($row['value']);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $items): void
    {
        if (empty($items)) {
            return;
        }

        $this->writeCount++;

        $this->db->exec('BEGIN TRANSACTION');

        try {
            foreach ($items as $key => $value) {
                $this->set($key, $value);
            }
            $this->db->exec('COMMIT');
        } catch (\Exception $e) {
            $this->db->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys): void
    {
        if (empty($keys)) {
            return;
        }

        $this->writeCount++;

        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $query = sprintf(
            'DELETE FROM %s WHERE key IN (%s)',
            $this->escapeIdentifier($this->table),
            $placeholders
        );

        $stmt = $this->db->prepare($query);

        foreach ($keys as $index => $key) {
            $stmt->bindValue($index + 1, $key, SQLITE3_TEXT);
        }

        $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->writeCount++;

        $query = sprintf(
            'DELETE FROM %s',
            $this->escapeIdentifier($this->table)
        );

        $this->db->exec($query);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function isMemoryDatabase(): bool
    {
        return $this->isMemory;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        if ($this->inTransaction) {
            $this->transactionCount++;

            return true;
        }

        $this->db->exec('BEGIN TRANSACTION');
        $this->inTransaction = true;
        $this->transactionCount = 1;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        if (! $this->inTransaction) {
            return false;
        }

        $this->transactionCount--;

        if ($this->transactionCount === 0) {
            $this->db->exec('COMMIT');
            $this->inTransaction = false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): bool
    {
        if (! $this->inTransaction) {
            return false;
        }

        $this->db->exec('ROLLBACK');
        $this->inTransaction = false;
        $this->transactionCount = 0;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $result = $this->db->query(sprintf(
            'SELECT COUNT(*) as count FROM %s',
            $this->escapeIdentifier($this->table)
        ));

        $row = $result->fetchArray(SQLITE3_ASSOC);

        return (int) ($row['count'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseSize(): int
    {
        if ($this->isMemory) {
            return 0;
        }

        $path = $this->databasePath;

        if (! file_exists($path)) {
            return 0;
        }

        return filesize($path) ?: 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): SqliteStorageStatsRecord
    {
        $totalItems = $this->count();
        $databaseSize = $this->getDatabaseSize();

        // Get page info from SQLite
        $pageSize = 4096; // Default
        $totalPages = 0;

        try {
            $result = $this->db->query('PRAGMA page_size');
            if ($result) {
                $row = $result->fetchArray(SQLITE3_ASSOC);
                if ($row) {
                    $pageSize = (int) $row['page_size'];
                }
            }

            $result = $this->db->query('PRAGMA page_count');
            if ($result) {
                $row = $result->fetchArray(SQLITE3_ASSOC);
                if ($row) {
                    $totalPages = (int) $row['page_count'];
                }
            }
        } catch (\Exception $e) {
            // Keep defaults
        }

        return new SqliteStorageStatsRecord(
            total_items: $totalItems,
            database_size: $databaseSize,
            database_path: $this->databasePath,
            table_name: $this->table,
            is_memory: $this->isMemory,
            active_transactions: $this->transactionCount,
            write_count: $this->writeCount,
            read_count: $this->readCount,
            page_size: $pageSize,
            total_pages: $totalPages,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function vacuum(): bool
    {
        if ($this->isMemory) {
            return false;
        }

        try {
            $this->db->exec('VACUUM');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(): SQLite3
    {
        return $this->db;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        try {
            $this->db->close();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Escapes an identifier for safe SQL usage.
     *
     * @param  string  $identifier  The identifier to escape
     * @return string Escaped identifier
     */
    private function escapeIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    /**
     * Destructor - ensures connection is closed.
     */
    public function __destruct()
    {
        if ($this->inTransaction) {
            try {
                $this->rollback();
            } catch (\Exception $e) {
                // Ignore on destruct
            }
        }

        try {
            $this->close();
        } catch (\Exception $e) {
            // Ignore on destruct
        }
    }
}
