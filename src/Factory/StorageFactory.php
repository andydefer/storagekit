<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Factory;

use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;
use AndyDefer\StorageKit\Contracts\StorageFactoryInterface;
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Enums\StorageSystem;
use AndyDefer\StorageKit\Records\CacheConfigRecord;
use AndyDefer\StorageKit\Storage\CacheStorage;
use AndyDefer\StorageKit\Storage\JsonlStorage;
use AndyDefer\StorageKit\Storage\MemoryStorage;

/**
 * Factory for creating storage instances.
 *
 * Supports creation of MemoryStorage, JsonlStorage, and CacheStorage.
 *
 * @example
 * $factory = new StorageFactory('/var/data', 3600);
 * $storage = $factory->create(StorageSystem::JSONL);
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
