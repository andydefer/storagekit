<?php

namespace AndyDefer\StorageKit\Tests\Unit\Storage;

use AndyDefer\StorageKit\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;

class MemoryStorageTest extends TestCase
{
    private MemoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new MemoryStorage;
    }

    // ============================================================
    // Tests pour set() et get()
    // ============================================================

    public function test_set_and_get(): void
    {
        $this->storage->set('key1', 'value1');
        $result = $this->storage->get('key1');

        $this->assertEquals('value1', $result);
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

        $this->storage->set($key, 'first_value');
        $this->storage->set($key, 'second_value');
        $result = $this->storage->get($key);

        $this->assertEquals('second_value', $result);
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
        $this->storage->set('existing_key', 'value');

        $result = $this->storage->exists('existing_key');
        $this->assertTrue($result);
    }

    public function test_exists_returns_false_for_non_existent_key(): void
    {
        $result = $this->storage->exists('non_existent_key');
        $this->assertFalse($result);
    }

    // ============================================================
    // Tests pour delete()
    // ============================================================

    public function test_delete_removes_existing_key(): void
    {
        $this->storage->set('key_to_delete', 'value');
        $this->assertTrue($this->storage->exists('key_to_delete'));

        $result = $this->storage->delete('key_to_delete');
        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists('key_to_delete'));
    }

    public function test_delete_returns_false_for_non_existent_key(): void
    {
        $result = $this->storage->delete('non_existent_key');
        $this->assertFalse($result);
    }

    public function test_clear(): void
    {
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->storage->set('key3', 'value3');

        $this->storage->clear();

        $this->assertFalse($this->storage->exists('key1'));
        $this->assertFalse($this->storage->exists('key2'));
        $this->assertFalse($this->storage->exists('key3'));
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

        $this->assertEquals($value, $result);
    }

    public function test_stores_integer_value(): void
    {
        $key = 'integer_key';
        $value = 42;

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertEquals($value, $result);
    }

    public function test_stores_float_value(): void
    {
        $key = 'float_key';
        $value = 3.14;

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertEquals($value, $result);
    }

    public function test_stores_boolean_value(): void
    {
        $key = 'boolean_key';
        $value = true;

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertEquals($value, $result);
    }

    public function test_stores_array_value(): void
    {
        $key = 'array_key';
        $value = ['a' => 1, 'b' => 2, 'c' => 3];

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

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

        $this->assertEquals($value, $result);
    }

    public function test_stores_object_value(): void
    {
        $key = 'object_key';
        $value = new \stdClass;
        $value->name = 'John';
        $value->age = 30;

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertEquals($value, $result);
    }

    public function test_stores_null_value(): void
    {
        $key = 'null_key';
        $value = null;

        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        $this->assertNull($result);
        $this->assertTrue($this->storage->exists($key));
    }

    // ============================================================
    // Tests pour le comportement du storage
    // ============================================================

    public function test_storage_preserves_data_order(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        foreach ($data as $key => $value) {
            $this->storage->set($key, $value);
        }

        $this->assertEquals('value1', $this->storage->get('key1'));
        $this->assertEquals('value2', $this->storage->get('key2'));
        $this->assertEquals('value3', $this->storage->get('key3'));
    }

    public function test_delete_does_not_affect_other_keys(): void
    {
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');

        $this->storage->delete('key1');

        $this->assertFalse($this->storage->exists('key1'));
        $this->assertTrue($this->storage->exists('key2'));
        $this->assertEquals('value2', $this->storage->get('key2'));
    }

    // ============================================================
    // Tests pour le typage
    // ============================================================

    public function test_get_returns_correct_type(): void
    {
        $this->storage->set('string', 'hello');
        $this->storage->set('int', 123);
        $this->storage->set('float', 45.67);
        $this->storage->set('bool', true);
        $this->storage->set('array', [1, 2, 3]);
        $this->storage->set('null', null);

        $this->assertIsString($this->storage->get('string'));
        $this->assertIsInt($this->storage->get('int'));
        $this->assertIsFloat($this->storage->get('float'));
        $this->assertIsBool($this->storage->get('bool'));
        $this->assertIsArray($this->storage->get('array'));
        $this->assertNull($this->storage->get('null'));
    }
}
