<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Contracts;

use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Enums\StorageSystem;
use AndyDefer\StorageKit\Records\CacheConfigRecord;
use AndyDefer\StorageKit\Storage\CacheStorage;
use AndyDefer\StorageKit\Storage\JsonlStorage;
use AndyDefer\StorageKit\Storage\MemoryStorage;

/**
 * Interface for storage factory implementations.
 *
 * Defines the contract for creating different types of storage instances.
 */
interface StorageFactoryInterface
{
    /**
     * Creates a storage instance based on the specified system.
     *
     * @param  StorageSystem  $system  The storage system to create
     * @return StorageInterface The created storage instance
     */
    public function create(StorageSystem $system): StorageInterface;

    /**
     * Creates a MemoryStorage instance.
     *
     * @return MemoryStorage In-memory storage
     */
    public function createMemoryStorage(): MemoryStorage;

    /**
     * Creates a JsonlStorage instance.
     *
     * @return JsonlStorage JSONL persistent storage
     */
    public function createJsonlStorage(): JsonlStorage;

    /**
     * Creates a CacheStorage instance.
     *
     * @param  CacheDriver  $driver  The cache driver to use
     * @param  CacheConfigRecord|null  $config  Optional cache configuration
     * @param  string  $cacheKeyPrefix  Prefix for cache keys
     * @return CacheStorage Cache storage instance
     */
    public function createCacheStorage(
        CacheDriver $driver = CacheDriver::FILES,
        ?CacheConfigRecord $config = null,
        string $cacheKeyPrefix = 'storage_',
    ): CacheStorage;

    /**
     * Creates a CacheStorage with default Files configuration.
     *
     * @return CacheStorage Cache storage with Files driver
     */
    public function createDefaultCacheStorage(): CacheStorage;

    /**
     * Creates a CacheStorage with SQLite driver.
     *
     * @return CacheStorage Cache storage with SQLite driver
     */
    public function createSqliteCacheStorage(): CacheStorage;

    /**
     * Sets the base path for persistent storage.
     *
     * @param  string  $basePath  Base directory path
     * @return self Fluent interface
     */
    public function setBasePath(string $basePath): self;

    /**
     * Sets the global Time-To-Live for storage.
     *
     * @param  int  $ttl  TTL in seconds
     * @return self Fluent interface
     */
    public function setTTL(int $ttl): self;

    /**
     * Sets the number of hash levels for JSONL storage.
     *
     * @param  int  $hashLevels  Number of hash levels
     * @return self Fluent interface
     */
    public function setHashLevels(int $hashLevels): self;

    /**
     * Returns the current base path.
     *
     * @return string Base directory path
     */
    public function getBasePath(): string;

    /**
     * Returns the current TTL.
     *
     * @return int TTL in seconds
     */
    public function getTTL(): int;

    /**
     * Returns the current hash levels.
     *
     * @return int Number of hash levels
     */
    public function getHashLevels(): int;
}
