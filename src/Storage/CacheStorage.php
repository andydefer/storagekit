<?php

namespace AndyDefer\StorageKit\Storage;

use AndyDefer\StorageKit\Contracts\Storage\CacheStorageInterface;
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Records\CacheConfigRecord;
use AndyDefer\StorageKit\Records\CacheStorageStatsRecord;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Entities\DriverStatistic;

class CacheStorage implements CacheStorageInterface
{
    private ExtendedCacheItemPoolInterface $cache;

    private CacheDriver $driver;

    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];

    private string $cacheKeyPrefix;

    public function __construct(
        CacheDriver $driver = CacheDriver::FILES,
        ?CacheConfigRecord $config = null,
        string $cacheKeyPrefix = 'algokit_',
    ) {
        $this->driver = $driver;
        $this->cacheKeyPrefix = $cacheKeyPrefix;

        $configArray = $this->buildConfig($driver, $config);
        $this->cache = CacheManager::getInstance($driver->value, new ConfigurationOption($configArray));
    }

    private function buildConfig(CacheDriver $driver, ?CacheConfigRecord $config): array
    {
        if ($config !== null) {
            return array_filter([
                'path' => $config->path,
            ], fn ($v) => $v !== null);
        }

        return $driver === CacheDriver::FILES
            ? ['path' => sys_get_temp_dir().'/algokit_cache']
            : ['path' => sys_get_temp_dir().'/algokit_cache.sqlite'];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit() && ! $item->isExpired()) {
            $this->stats['hits']++;

            return $item->get();
        }

        if ($item->isHit() && $item->isExpired()) {
            $this->cache->deleteItem($cacheKey);
            $this->stats['deletes']++;
        }

        $this->stats['misses']++;

        return $default;
    }

    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function set(string $key, mixed $value): void
    {
        $cacheKey = $this->getCacheKey($key);
        $item = $this->cache->getItem($cacheKey);
        $item->set($value);
        $this->cache->save($item);
        $this->stats['sets']++;
    }

    public function setMultiple(array $items): void
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function setWithTTL(string $key, mixed $value, int $ttl): void
    {
        $cacheKey = $this->getCacheKey($key);
        $item = $this->cache->getItem($cacheKey);
        $item->set($value)->expiresAfter($ttl);
        $this->cache->save($item);
        $this->stats['sets']++;
    }

    public function delete(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $result = $this->cache->deleteItem($cacheKey);

        if ($result) {
            $this->stats['deletes']++;
        }

        return $result;
    }

    public function deleteMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    public function exists(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit() && $item->isExpired()) {
            $this->cache->deleteItem($cacheKey);

            return false;
        }

        return $item->isHit();
    }

    public function clear(): void
    {
        $this->cache->clear();
        $this->stats = ['hits' => 0, 'misses' => 0, 'sets' => 0, 'deletes' => 0];
    }

    public function getStats(): CacheStorageStatsRecord
    {
        $itemsCount = 0;

        try {
            if (method_exists($this->cache, 'getStats')) {
                /** @var DriverStatistic $driverStats */
                $driverStats = $this->cache->getStats();
                $itemsCount = $driverStats->getCount() ?? 0;
            }
        } catch (\Exception $e) {
            // Ignorer l'erreur
        }

        return new CacheStorageStatsRecord(
            driver: $this->driver,
            hits: $this->stats['hits'],
            misses: $this->stats['misses'],
            sets: $this->stats['sets'],
            deletes: $this->stats['deletes'],
            items_count: $itemsCount,
        );
    }

    public function getDriver(): ExtendedCacheItemPoolInterface
    {
        return $this->cache;
    }

    public function getDriverName(): string
    {
        return $this->driver->value;
    }

    public function setTTL(string $key, int $seconds): void
    {
        $cacheKey = $this->getCacheKey($key);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $item->expiresAfter($seconds);
            $this->cache->save($item);
        }
    }

    public function getCacheKeyPrefix(): string
    {
        return $this->cacheKeyPrefix;
    }

    public function setCacheKeyPrefix(string $prefix): void
    {
        $this->cacheKeyPrefix = $prefix;
    }

    private function getCacheKey(string $key): string
    {
        return $this->cacheKeyPrefix.$key;
    }
}
