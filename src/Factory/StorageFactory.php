<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Factory;

use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;
use AndyDefer\StorageKit\Contracts\StorageFactoryInterface;
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Enums\StorageSystem;
use AndyDefer\StorageKit\Records\CacheConfigRecord;
use AndyDefer\StorageKit\Storage\CacheStorage;
use AndyDefer\StorageKit\Storage\CookieStorage;
use AndyDefer\StorageKit\Storage\JsonlStorage;
use AndyDefer\StorageKit\Storage\MemoryStorage;
use AndyDefer\StorageKit\Storage\SessionStorage;
use AndyDefer\StorageKit\Storage\SqliteStorage;

/**
 * Factory for creating storage instances.
 *
 * Supports creation of MemoryStorage, JsonlStorage, CacheStorage, SessionStorage,
 * CookieStorage, and SqliteStorage.
 *
 * @example
 * $factory = new StorageFactory('/var/data', 3600);
 * $storage = $factory->create(StorageSystem::JSONL);
 * $sqlite = $factory->createSqliteStorage('/path/to/data.db');
 */
final class StorageFactory implements StorageFactoryInterface
{
    private string $basePath;

    private int $ttl;

    private int $hashLevels;

    public function __construct(
        string $basePath = '/tmp/storage',
        int $ttl = 86400,
        int $hashLevels = 2
    ) {
        $this->basePath = rtrim($basePath, '/');
        $this->ttl = $ttl;
        $this->hashLevels = $hashLevels;
    }

    public function create(StorageSystem $system): StorageInterface
    {
        return match ($system) {
            StorageSystem::MEMORY => $this->createMemoryStorage(),
            StorageSystem::JSONL => $this->createJsonlStorage(),
            StorageSystem::CACHE => $this->createCacheStorage(),
            StorageSystem::SESSION => $this->createSessionStorage(),
            StorageSystem::COOKIE => $this->createCookieStorage(),
            StorageSystem::SQLITE => $this->createSqliteStorage(),
        };
    }

    public function createMemoryStorage(): MemoryStorage
    {
        return new MemoryStorage;
    }

    public function createJsonlStorage(): JsonlStorage
    {
        return new JsonlStorage(
            basePath: $this->basePath,
            ttl: $this->ttl,
            hashLevels: $this->hashLevels,
        );
    }

    public function createCacheStorage(
        CacheDriver $driver = CacheDriver::FILES,
        ?CacheConfigRecord $config = null,
        string $cacheKeyPrefix = 'storage_',
    ): CacheStorage {
        return new CacheStorage(
            driver: $driver,
            config: $config,
            cacheKeyPrefix: $cacheKeyPrefix,
        );
    }

    public function createDefaultCacheStorage(): CacheStorage
    {
        $config = new CacheConfigRecord(
            path: $this->basePath.'/cache',
        );

        return new CacheStorage(
            driver: CacheDriver::FILES,
            config: $config,
            cacheKeyPrefix: 'storage_',
        );
    }

    public function createSqliteCacheStorage(): CacheStorage
    {
        $config = new CacheConfigRecord(
            path: $this->basePath.'/cache.sqlite',
        );

        return new CacheStorage(
            driver: CacheDriver::SQLITE,
            config: $config,
            cacheKeyPrefix: 'storage_',
        );
    }

    public function createSessionStorage(string $namespace = 'storage_kit'): SessionStorage
    {
        return new SessionStorage($namespace);
    }

    public function createCookieStorage(
        string $prefix = 'storage_',
        ?int $expires = null,
        ?string $domain = null,
        string $path = '/',
        bool $secure = false,
        bool $httpOnly = true,
        ?string $sameSite = 'Lax'
    ): CookieStorage {
        return new CookieStorage(
            prefix: $prefix,
            expires: $expires,
            domain: $domain,
            path: $path,
            secure: $secure,
            httpOnly: $httpOnly,
            sameSite: $sameSite
        );
    }

    /**
     * Creates a SQLite storage instance.
     *
     * @param  string  $database  Path to SQLite database file (':memory:' for in-memory)
     * @param  string  $table  Table name for key-value storage
     * @return SqliteStorage The SQLite storage instance
     */
    public function createSqliteStorage(
        string $database = ':memory:',
        string $table = 'storage_kv'
    ): SqliteStorage {
        return new SqliteStorage($database, $table);
    }

    /**
     * Creates a persistent SQLite storage with configured path.
     *
     * @param  string  $filename  Filename within base path
     * @param  string  $table  Table name for key-value storage
     * @return SqliteStorage The persistent SQLite storage instance
     */
    public function createPersistentSqliteStorage(
        string $filename = 'storage.db',
        string $table = 'storage_kv'
    ): SqliteStorage {
        $path = $this->basePath.'/'.ltrim($filename, '/');

        return new SqliteStorage($path, $table);
    }

    public function setBasePath(string $basePath): self
    {
        $this->basePath = rtrim($basePath, '/');

        return $this;
    }

    public function setTTL(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function setHashLevels(int $hashLevels): self
    {
        $this->hashLevels = $hashLevels;

        return $this;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getTTL(): int
    {
        return $this->ttl;
    }

    public function getHashLevels(): int
    {
        return $this->hashLevels;
    }
}
