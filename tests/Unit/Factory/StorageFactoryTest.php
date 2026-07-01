<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Tests\Unit\Factory;

use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Enums\StorageSystem;
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Records\CacheConfigRecord;
use AndyDefer\StorageKit\Storage\CacheStorage;
use AndyDefer\StorageKit\Storage\CookieStorage;
use AndyDefer\StorageKit\Storage\JsonlStorage;
use AndyDefer\StorageKit\Storage\MemoryStorage;
use AndyDefer\StorageKit\Storage\SessionStorage;
use AndyDefer\StorageKit\Storage\SqliteStorage;
use PHPUnit\Framework\TestCase;

final class StorageFactoryTest extends TestCase
{
    private StorageFactory $factory;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/storage_test_'.uniqid();
        $this->factory = new StorageFactory($this->tempDir, 3600, 2);

        $_COOKIE = [];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_COOKIE = [];
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    // ============================================================
    // Factory Creation Tests
    // ============================================================

    public function test_create_memory_storage(): void
    {
        // Act
        $storage = $this->factory->create(StorageSystem::MEMORY);

        // Assert
        $this->assertInstanceOf(MemoryStorage::class, $storage);
        $this->assertInstanceOf(StorageInterface::class, $storage);
    }

    public function test_create_jsonl_storage(): void
    {
        // Act
        $storage = $this->factory->create(StorageSystem::JSONL);

        // Assert
        $this->assertInstanceOf(JsonlStorage::class, $storage);
        $this->assertInstanceOf(StorageInterface::class, $storage);
    }

    public function test_create_cache_storage(): void
    {
        // Act
        $storage = $this->factory->create(StorageSystem::CACHE);

        // Assert
        $this->assertInstanceOf(CacheStorage::class, $storage);
        $this->assertInstanceOf(StorageInterface::class, $storage);
    }

    public function test_create_session_storage(): void
    {
        // Act
        $storage = $this->factory->create(StorageSystem::SESSION);

        // Assert
        $this->assertInstanceOf(SessionStorage::class, $storage);
        $this->assertInstanceOf(StorageInterface::class, $storage);
    }

    public function test_create_cookie_storage(): void
    {
        // Act
        $storage = $this->factory->create(StorageSystem::COOKIE);

        // Assert
        $this->assertInstanceOf(CookieStorage::class, $storage);
        $this->assertInstanceOf(StorageInterface::class, $storage);
    }

    public function test_create_sqlite_storage(): void
    {
        // Act
        $storage = $this->factory->create(StorageSystem::SQLITE);

        // Assert
        $this->assertInstanceOf(SqliteStorage::class, $storage);
        $this->assertInstanceOf(StorageInterface::class, $storage);
    }

    // ============================================================
    // Specific Creation Methods Tests
    // ============================================================

    public function test_create_memory_storage_method(): void
    {
        // Act
        $storage = $this->factory->createMemoryStorage();

        // Assert
        $this->assertInstanceOf(MemoryStorage::class, $storage);
    }

    public function test_create_jsonl_storage_method(): void
    {
        // Act
        $storage = $this->factory->createJsonlStorage();

        // Assert
        $this->assertInstanceOf(JsonlStorage::class, $storage);
    }

    public function test_create_cache_storage_method(): void
    {
        // Act
        $storage = $this->factory->createCacheStorage();

        // Assert
        $this->assertInstanceOf(CacheStorage::class, $storage);
    }

    public function test_create_session_storage_method(): void
    {
        // Arrange
        $namespace = 'test_namespace';

        // Act
        $storage = $this->factory->createSessionStorage($namespace);

        // Assert
        $this->assertInstanceOf(SessionStorage::class, $storage);
        $this->assertSame($namespace, $storage->getNamespace());
    }

    public function test_create_cookie_storage_method(): void
    {
        // Arrange
        $prefix = 'test_';
        $path = '/admin';

        // Act
        $storage = $this->factory->createCookieStorage(
            prefix: $prefix,
            path: $path,
            secure: true,
            httpOnly: false,
            sameSite: 'Strict'
        );

        // Assert
        $this->assertInstanceOf(CookieStorage::class, $storage);
        $this->assertSame($prefix, $storage->getPrefix());
        $this->assertSame($path, $storage->getPath());
        $this->assertTrue($storage->isSecure());
        $this->assertFalse($storage->isHttpOnly());
        $this->assertSame('Strict', $storage->getSameSite());

        $storage->clear();
    }

    public function test_create_sqlite_storage_method(): void
    {
        // Arrange
        $database = $this->tempDir.'/test.db';
        $table = 'custom_kv';

        // Act
        $storage = $this->factory->createSqliteStorage($database, $table);

        // Assert
        $this->assertInstanceOf(SqliteStorage::class, $storage);
        $this->assertSame($database, $storage->getDatabasePath());
        $this->assertSame($table, $storage->getTableName());
        $this->assertFalse($storage->isMemoryDatabase());

        $storage->clear();
        $storage->close();
    }

    public function test_create_sqlite_storage_method_in_memory(): void
    {
        // Act
        $storage = $this->factory->createSqliteStorage(':memory:', 'test_table');

        // Assert
        $this->assertInstanceOf(SqliteStorage::class, $storage);
        $this->assertSame(':memory:', $storage->getDatabasePath());
        $this->assertSame('test_table', $storage->getTableName());
        $this->assertTrue($storage->isMemoryDatabase());

        $storage->clear();
        $storage->close();
    }

    public function test_create_persistent_sqlite_storage(): void
    {
        // Act
        $storage = $this->factory->createPersistentSqliteStorage('mydata.db', 'my_kv');

        // Assert
        $expectedPath = $this->tempDir.'/mydata.db';
        $this->assertInstanceOf(SqliteStorage::class, $storage);
        $this->assertSame($expectedPath, $storage->getDatabasePath());
        $this->assertSame('my_kv', $storage->getTableName());
        $this->assertFalse($storage->isMemoryDatabase());

        $storage->clear();
        $storage->close();
    }

    public function test_create_cache_storage_with_custom_driver(): void
    {
        // Act
        $storage = $this->factory->createCacheStorage(CacheDriver::SQLITE);

        // Assert
        $this->assertInstanceOf(CacheStorage::class, $storage);
        $this->assertSame('Sqlite', $storage->getDriverName());
    }

    public function test_create_cache_storage_with_custom_config(): void
    {
        // Arrange
        $config = new CacheConfigRecord($this->tempDir.'/custom_cache');

        // Act
        $storage = $this->factory->createCacheStorage(CacheDriver::FILES, $config);

        // Assert
        $this->assertInstanceOf(CacheStorage::class, $storage);
    }

    public function test_create_cache_storage_with_custom_prefix(): void
    {
        // Act
        $storage = $this->factory->createCacheStorage(
            driver: CacheDriver::FILES,
            config: null,
            cacheKeyPrefix: 'custom_'
        );

        // Assert
        $this->assertInstanceOf(CacheStorage::class, $storage);
        $this->assertSame('custom_', $storage->getCacheKeyPrefix());
    }

    // ============================================================
    // Default Cache Storage Tests
    // ============================================================

    public function test_create_default_cache_storage(): void
    {
        // Act
        $storage = $this->factory->createDefaultCacheStorage();

        // Assert
        $this->assertInstanceOf(CacheStorage::class, $storage);
        $this->assertSame('Files', $storage->getDriverName());
    }

    public function test_create_sqlite_cache_storage(): void
    {
        // Act
        $storage = $this->factory->createSqliteCacheStorage();

        // Assert
        $this->assertInstanceOf(CacheStorage::class, $storage);
        $this->assertSame('Sqlite', $storage->getDriverName());
    }

    // ============================================================
    // Configuration Tests
    // ============================================================

    public function test_set_and_get_base_path(): void
    {
        // Arrange
        $newPath = $this->tempDir.'/new_path';

        // Act
        $this->factory->setBasePath($newPath);

        // Assert
        $this->assertSame($newPath, $this->factory->getBasePath());
    }

    public function test_set_and_get_ttl(): void
    {
        // Act
        $this->factory->setTTL(7200);

        // Assert
        $this->assertSame(7200, $this->factory->getTTL());
    }

    public function test_set_and_get_hash_levels(): void
    {
        // Act
        $this->factory->setHashLevels(3);

        // Assert
        $this->assertSame(3, $this->factory->getHashLevels());
    }

    public function test_base_path_trimming(): void
    {
        // Act
        $this->factory->setBasePath($this->tempDir.'/trailing/');

        // Assert
        $this->assertSame($this->tempDir.'/trailing', $this->factory->getBasePath());
    }

    public function test_fluent_interface(): void
    {
        // Act
        $result = $this->factory
            ->setBasePath($this->tempDir.'/new_path')
            ->setTTL(7200)
            ->setHashLevels(3);

        // Assert
        $this->assertSame($this->factory, $result);
        $this->assertSame($this->tempDir.'/new_path', $this->factory->getBasePath());
        $this->assertSame(7200, $this->factory->getTTL());
        $this->assertSame(3, $this->factory->getHashLevels());
    }

    // ============================================================
    // Integration Tests
    // ============================================================

    public function test_factory_creates_storage_with_correct_configuration(): void
    {
        // Arrange
        $factory = new StorageFactory($this->tempDir.'/test_data', 1800, 4);

        // Act
        $storage = $factory->create(StorageSystem::JSONL);

        // Assert
        $this->assertInstanceOf(JsonlStorage::class, $storage);

        $stats = $storage->getStats();
        $this->assertSame($this->tempDir.'/test_data', $stats->base_path);
        $this->assertSame(1800, $stats->ttl);
    }

    public function test_factory_creates_different_storages_independently(): void
    {
        // Arrange
        $factory = new StorageFactory($this->tempDir);

        // Act
        $memory = $factory->create(StorageSystem::MEMORY);
        $jsonl = $factory->create(StorageSystem::JSONL);
        $cache = $factory->create(StorageSystem::CACHE);
        $session = $factory->create(StorageSystem::SESSION);
        $cookie = $factory->create(StorageSystem::COOKIE);
        $sqlite = $factory->create(StorageSystem::SQLITE);

        // Assert
        $this->assertInstanceOf(MemoryStorage::class, $memory);
        $this->assertInstanceOf(JsonlStorage::class, $jsonl);
        $this->assertInstanceOf(CacheStorage::class, $cache);
        $this->assertInstanceOf(SessionStorage::class, $session);
        $this->assertInstanceOf(CookieStorage::class, $cookie);
        $this->assertInstanceOf(SqliteStorage::class, $sqlite);

        $cookie->clear();
        $session->clear();
        $sqlite->clear();
        $sqlite->close();
    }
}
