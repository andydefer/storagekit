<?php

namespace AndyDefer\StorageKit\Tests\Unit\Storage;

use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Records\CacheConfigRecord;
use AndyDefer\StorageKit\Records\CacheStorageStatsRecord;
use AndyDefer\StorageKit\Storage\CacheStorage;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use PHPUnit\Framework\TestCase;

class CacheStorageTest extends TestCase
{
    private CacheStorage $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheStorage;
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
    }

    // ============================================================
    // Tests de base
    // ============================================================

    public function test_set_and_get(): void
    {
        $key = 'test_key';
        $value = ['name' => 'John', 'age' => 30];

        $this->cache->set($key, $value);
        $result = $this->cache->get($key);

        $this->assertEquals($value, $result);
    }

    public function test_get_returns_default_when_key_not_found(): void
    {
        $result = $this->cache->get('non_existent', 'default');
        $this->assertEquals('default', $result);
    }

    public function test_get_returns_null_when_key_not_found_and_no_default(): void
    {
        $result = $this->cache->get('non_existent');
        $this->assertNull($result);
    }

    public function test_set_overwrites_existing_key(): void
    {
        $key = 'test_key';
        $this->cache->set($key, 'first_value');
        $this->cache->set($key, 'second_value');

        $result = $this->cache->get($key);
        $this->assertEquals('second_value', $result);
    }

    // ============================================================
    // Tests batch
    // ============================================================

    public function test_get_multiple(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $result = $this->cache->getMultiple(['key1', 'key2', 'key3']);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], $result);
    }

    public function test_get_multiple_with_missing_keys(): void
    {
        $this->cache->set('key1', 'value1');

        $result = $this->cache->getMultiple(['key1', 'key2', 'key3']);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => null,
            'key3' => null,
        ], $result);
    }

    public function test_set_multiple(): void
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $this->cache->setMultiple($items);

        $this->assertEquals('value1', $this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));
    }

    public function test_delete_multiple(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $this->cache->deleteMultiple(['key1', 'key2']);

        $this->assertFalse($this->cache->exists('key1'));
        $this->assertFalse($this->cache->exists('key2'));
        $this->assertTrue($this->cache->exists('key3'));
    }

    // ============================================================
    // Tests pour exists()
    // ============================================================

    public function test_exists_returns_true_for_existing_key(): void
    {
        $this->cache->set('existing_key', 'value');
        $this->assertTrue($this->cache->exists('existing_key'));
    }

    public function test_exists_returns_false_for_non_existent_key(): void
    {
        $this->assertFalse($this->cache->exists('non_existent_key'));
    }

    // ============================================================
    // Tests pour delete()
    // ============================================================

    public function test_delete_removes_existing_key(): void
    {
        $key = 'key_to_delete';
        $this->cache->set($key, 'value');
        $this->assertTrue($this->cache->exists($key));

        $result = $this->cache->delete($key);
        $this->assertTrue($result);
        $this->assertFalse($this->cache->exists($key));
    }

    public function test_delete_returns_false_for_non_existent_key(): void
    {
        $result = $this->cache->delete('non_existent_key');
        $this->assertFalse($result);
    }

    // ============================================================
    // Tests pour clear()
    // ============================================================

    public function test_clear_removes_all_data(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $this->assertTrue($this->cache->exists('key1'));
        $this->assertTrue($this->cache->exists('key2'));
        $this->assertTrue($this->cache->exists('key3'));

        $this->cache->clear();

        $this->assertFalse($this->cache->exists('key1'));
        $this->assertFalse($this->cache->exists('key2'));
        $this->assertFalse($this->cache->exists('key3'));
    }

    // ============================================================
    // Tests pour TTL
    // ============================================================

    public function test_set_ttl_expires_data(): void
    {
        $key = 'expiring_key';
        $this->cache->setWithTTL($key, 'value', 2);

        $this->assertTrue($this->cache->exists($key));

        sleep(3);

        $this->assertNull($this->cache->get($key));
    }

    public function test_set_ttl_after_set(): void
    {
        $key = 'ttl_after_set';
        $this->cache->set($key, 'value');
        $this->cache->setTTL($key, 2);

        $this->assertTrue($this->cache->exists($key));

        sleep(3);

        $this->assertNull($this->cache->get($key));
    }

    public function test_set_ttl_very_short(): void
    {
        $key = 'very_short_ttl';
        $this->cache->setWithTTL($key, 'value', 1);

        $this->assertTrue($this->cache->exists($key));

        sleep(2);

        $this->assertNull($this->cache->get($key));
    }

    // ============================================================
    // Tests pour getStats()
    // ============================================================

    public function test_get_stats_returns_statistics(): void
    {
        $this->cache->get('non_existent');
        $this->cache->set('key1', 'value1');
        $this->cache->get('key1');

        $stats = $this->cache->getStats();

        $this->assertInstanceOf(CacheStorageStatsRecord::class, $stats);
        $this->assertEquals(CacheDriver::FILES, $stats->driver);
        $this->assertEquals(1, $stats->hits);
        $this->assertEquals(1, $stats->misses);
        $this->assertEquals(1, $stats->sets);
        $this->assertGreaterThanOrEqual(0, $stats->deletes);
        $this->assertGreaterThanOrEqual(0, $stats->items_count);
    }

    // ============================================================
    // Tests avec différents types de données
    // ============================================================

    public function test_stores_string_value(): void
    {
        $this->cache->set('string_key', 'Hello World');
        $result = $this->cache->get('string_key');
        $this->assertEquals('Hello World', $result);
    }

    public function test_stores_integer_value(): void
    {
        $this->cache->set('integer_key', 42);
        $result = $this->cache->get('integer_key');
        $this->assertEquals(42, $result);
    }

    public function test_stores_boolean_value(): void
    {
        $this->cache->set('boolean_key', true);
        $result = $this->cache->get('boolean_key');
        $this->assertTrue($result);
    }

    public function test_stores_array_value(): void
    {
        $value = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->cache->set('array_key', $value);
        $result = $this->cache->get('array_key');
        $this->assertEquals($value, $result);
    }

    public function test_stores_nested_array_value(): void
    {
        $value = [
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
                'preferences' => ['dark_mode' => true],
            ],
        ];

        $this->cache->set('nested_key', $value);
        $result = $this->cache->get('nested_key');
        $this->assertEquals($value, $result);
    }

    // ============================================================
    // Tests du préfixe de clé
    // ============================================================

    public function test_cache_key_prefix(): void
    {
        $cache = new CacheStorage(
            driver: CacheDriver::FILES,
            config: null,
            cacheKeyPrefix: 'app_'
        );

        $cache->set('test', 'value');
        $this->assertEquals('value', $cache->get('test'));
    }

    // ============================================================
    // Tests du driver
    // ============================================================

    public function test_get_driver_returns_driver_instance(): void
    {
        $driver = $this->cache->getDriver();
        $this->assertInstanceOf(ExtendedCacheItemPoolInterface::class, $driver);
    }

    public function test_get_driver_name(): void
    {
        $this->assertEquals('Files', $this->cache->getDriverName());
    }

    // ============================================================
    // Tests avec Sqlite
    // ============================================================

    public function test_sqlite_driver(): void
    {
        $config = new CacheConfigRecord(
            path: sys_get_temp_dir().'/algokit_sqlite_test.sqlite'
        );

        $cache = new CacheStorage(CacheDriver::SQLITE, $config);

        $cache->set('sqlite_key', 'sqlite_value');
        $result = $cache->get('sqlite_key');

        $this->assertEquals('sqlite_value', $result);

        $cache->clear();
    }

    // ============================================================
    // Tests avec config personnalisée
    // ============================================================

    public function test_custom_config(): void
    {
        $config = new CacheConfigRecord(
            path: sys_get_temp_dir().'/custom_cache'
        );

        $cache = new CacheStorage(CacheDriver::FILES, $config);

        $cache->set('custom_key', 'custom_value');
        $result = $cache->get('custom_key');

        $this->assertEquals('custom_value', $result);

        $cache->clear();
    }
}
