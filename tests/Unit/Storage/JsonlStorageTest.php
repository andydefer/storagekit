<?php

namespace AndyDefer\StorageKit\Tests\Unit\Storage;

use AndyDefer\PhpJsonl\JsonlService;
use AndyDefer\StorageKit\Records\JsonlStorageStatsRecord;
use AndyDefer\StorageKit\Storage\JsonlStorage;
use PHPUnit\Framework\TestCase;

class JsonlStorageTest extends TestCase
{
    private JsonlStorage $storage;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/algokit_test_'.uniqid();
        $this->storage = new JsonlStorage($this->tempDir, 3600, 2);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // ============================================================
    // Tests pour set() et get()
    // ============================================================

    public function test_set_and_get(): void
    {
        $key = 'user_123';
        $value = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertEquals($value, $result);
    }

    public function test_get_returns_default_when_key_not_found(): void
    {
        $result = $this->storage->get('non_existent_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function test_get_returns_null_when_key_not_found_and_no_default(): void
    {
        $result = $this->storage->get('non_existent_key');
        $this->assertNull($result);
    }

    public function test_set_overwrites_existing_key(): void
    {
        $key = 'test_key';
        $value1 = ['data' => 'first'];
        $value2 = ['data' => 'second'];

        $this->storage->set($key, $value1);
        $this->storage->set($key, $value2);
        $result = $this->storage->get($key);

        $this->assertEquals($value2, $result);
    }

    // ============================================================
    // Tests batch
    // ============================================================

    public function test_get_multiple(): void
    {
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->storage->set('key3', 'value3');

        $result = $this->storage->getMultiple(['key1', 'key2', 'key3']);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], $result);
    }

    public function test_get_multiple_with_missing_keys(): void
    {
        $this->storage->set('key1', 'value1');

        $result = $this->storage->getMultiple(['key1', 'key2', 'key3']);

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

        $this->storage->setMultiple($items);

        $this->assertEquals('value1', $this->storage->get('key1'));
        $this->assertEquals('value2', $this->storage->get('key2'));
        $this->assertEquals('value3', $this->storage->get('key3'));
    }

    public function test_delete_multiple(): void
    {
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->storage->set('key3', 'value3');

        $this->storage->deleteMultiple(['key1', 'key2']);

        $this->assertFalse($this->storage->exists('key1'));
        $this->assertFalse($this->storage->exists('key2'));
        $this->assertTrue($this->storage->exists('key3'));
    }

    // ============================================================
    // Tests pour exists()
    // ============================================================

    public function test_exists_returns_true_for_existing_key(): void
    {
        $key = 'existing_key';
        $this->storage->set($key, ['data' => 'test']);

        $result = $this->storage->exists($key);

        $this->assertTrue($result);
    }

    public function test_exists_returns_false_for_non_existent_key(): void
    {
        $result = $this->storage->exists('non_existent_key');
        $this->assertFalse($result);
    }

    public function test_exists_returns_false_for_expired_key(): void
    {
        $storage = new JsonlStorage($this->tempDir, 1, 2);
        $key = 'expiring_key';
        $storage->set($key, ['data' => 'test']);

        sleep(2);

        $result = $storage->exists($key);
        $this->assertFalse($result);
    }

    // ============================================================
    // Tests pour delete()
    // ============================================================

    public function test_delete_removes_existing_key(): void
    {
        $key = 'key_to_delete';
        $this->storage->set($key, ['data' => 'test']);

        $this->assertTrue($this->storage->exists($key));

        $result = $this->storage->delete($key);
        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists($key));
    }

    public function test_delete_returns_false_for_non_existent_key(): void
    {
        $result = $this->storage->delete('non_existent_key');
        $this->assertFalse($result);
    }

    public function test_delete_on_expired_key_removes_file(): void
    {
        $storage = new JsonlStorage($this->tempDir, 1, 2);
        $key = 'expired_key';
        $storage->set($key, ['data' => 'test']);

        sleep(2);

        $storage->get($key);

        $result = $storage->exists($key);
        $this->assertFalse($result);
    }

    // ============================================================
    // Tests pour clear()
    // ============================================================

    public function test_clear_removes_all_data(): void
    {
        $this->storage->set('key1', ['data' => 'value1']);
        $this->storage->set('key2', ['data' => 'value2']);
        $this->storage->set('key3', ['data' => 'value3']);

        $this->assertTrue($this->storage->exists('key1'));
        $this->assertTrue($this->storage->exists('key2'));
        $this->assertTrue($this->storage->exists('key3'));

        $this->storage->clear();

        $this->assertFalse($this->storage->exists('key1'));
        $this->assertFalse($this->storage->exists('key2'));
        $this->assertFalse($this->storage->exists('key3'));
    }

    // ============================================================
    // Tests pour cleanExpired()
    // ============================================================

    public function test_clean_expired_removes_only_expired_entries(): void
    {
        $storage = new JsonlStorage($this->tempDir, 1, 2);

        $storage->set('will_expire', ['data' => 'expired']);
        $storage->setTTL(3600);
        $storage->set('will_stay', ['data' => 'valid']);

        sleep(2);

        $deletedCount = $storage->cleanExpired();

        $this->assertEquals(1, $deletedCount);
        $this->assertFalse($storage->exists('will_expire'));
        $this->assertTrue($storage->exists('will_stay'));
    }

    // ============================================================
    // Tests pour saveState() et loadState()
    // ============================================================

    public function test_save_state_and_load_state(): void
    {
        $key = 'trie_state';
        $state = ['root' => ['children' => [], 'words' => ['laravel', 'php']]];

        $this->storage->saveState($key, $state);
        $result = $this->storage->loadState($key);

        $this->assertEquals($state, $result);
    }

    public function test_save_state_with_context_and_load_state_with_context(): void
    {
        $key = 'trie_state';
        $context = 'french';
        $state = ['root' => ['children' => [], 'words' => ['bonjour', 'salut']]];

        $this->storage->saveState($key, $state, $context);
        $result = $this->storage->loadState($key, $context);

        $this->assertEquals($state, $result);
    }

    public function test_load_state_returns_null_for_non_existent_key(): void
    {
        $result = $this->storage->loadState('non_existent_state');
        $this->assertNull($result);
    }

    // ============================================================
    // Tests pour TTL
    // ============================================================

    public function test_set_ttl_changes_expiration(): void
    {
        $storage = new JsonlStorage($this->tempDir, 10, 2);
        $key = 'ttl_test';

        $storage->setTTL(5);
        $storage->set($key, ['data' => 'test']);
        $resultBefore = $storage->get($key);

        $this->assertEquals(['data' => 'test'], $resultBefore);

        $ttl = $storage->getTTL();
        $this->assertEquals(5, $ttl);
    }

    // ============================================================
    // Tests pour getStats()
    // ============================================================

    public function test_get_stats_returns_statistics(): void
    {
        $this->storage->set('key1', ['data' => 'value1']);
        $this->storage->set('key2', ['data' => 'value2']);

        $stats = $this->storage->getStats();

        $this->assertInstanceOf(JsonlStorageStatsRecord::class, $stats);
        $this->assertGreaterThanOrEqual(0, $stats->total_lines_processed);
        $this->assertGreaterThanOrEqual(0, $stats->processed_files);
        $this->assertEquals($this->tempDir, $stats->base_path);
        $this->assertEquals(3600, $stats->ttl);
    }

    // ============================================================
    // Tests pour getJsonlService()
    // ============================================================

    public function test_get_jsonl_service_returns_service_instance(): void
    {
        $service = $this->storage->getJsonlService();
        $this->assertInstanceOf(JsonlService::class, $service);
    }

    // ============================================================
    // Tests pour le sanitize des clés
    // ============================================================

    public function test_sanitize_handles_special_characters_in_key(): void
    {
        $key = 'user/with/slashes?and&special@chars';
        $value = ['data' => 'test'];

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertEquals($value, $result);
    }

    // ============================================================
    // Tests avec différents types de données
    // ============================================================

    public function test_stores_string_value(): void
    {
        $key = 'string_key';
        $value = 'Hello World';

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertIsString($result);
        $this->assertEquals($value, $result);
    }

    public function test_stores_integer_value(): void
    {
        $key = 'integer_key';
        $value = 42;

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertIsInt($result);
        $this->assertEquals($value, $result);
    }

    public function test_stores_boolean_value(): void
    {
        $key = 'boolean_key';
        $value = true;

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertIsBool($result);
        $this->assertEquals($value, $result);
    }

    public function test_stores_array_value(): void
    {
        $key = 'array_key';
        $value = ['a' => 1, 'b' => 2, 'c' => 3];

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertIsArray($result);
        $this->assertEquals($value, $result);
    }

    public function test_stores_nested_array_value(): void
    {
        $key = 'nested_array_key';
        $value = [
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
                'preferences' => ['dark_mode' => true, 'notifications' => false],
            ],
        ];

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertIsArray($result);
        $this->assertEquals($value, $result);
    }
}
