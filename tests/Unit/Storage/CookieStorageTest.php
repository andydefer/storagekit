<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Tests\Unit\Storage;

use AndyDefer\StorageKit\Storage\CookieStorage;
use PHPUnit\Framework\TestCase;

final class CookieStorageTest extends TestCase
{
    private CookieStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $_COOKIE = [];

        $this->storage = new CookieStorage(
            prefix: 'test_',
            expires: time() + 3600,
            path: '/',
            secure: false,
            httpOnly: true,
            sameSite: 'Lax'
        );

        $this->storage->clear();
    }

    protected function tearDown(): void
    {
        $this->storage->clear();
        $_COOKIE = [];
        parent::tearDown();
    }

    public function test_set_and_get(): void
    {
        // Arrange
        $key = 'user_123';
        $value = ['name' => 'John', 'age' => 30];

        // Act
        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        // Assert
        $this->assertSame($value, $result);
    }

    public function test_get_returns_default_when_key_not_found(): void
    {
        // Act
        $result = $this->storage->get('non_existent', 'default');

        // Assert
        $this->assertSame('default', $result);
    }

    public function test_get_returns_null_when_key_not_found_and_no_default(): void
    {
        // Act
        $result = $this->storage->get('non_existent');

        // Assert
        $this->assertNull($result);
    }

    public function test_set_overwrites_existing_key(): void
    {
        // Arrange
        $key = 'test_key';

        // Act
        $this->storage->set($key, 'first_value');
        $this->storage->set($key, 'second_value');
        $result = $this->storage->get($key);

        // Assert
        $this->assertSame('second_value', $result);
    }

    public function test_get_multiple(): void
    {
        // Arrange
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->storage->set('key3', 'value3');

        // Act
        $result = $this->storage->getMultiple(['key1', 'key2', 'key3']);

        // Assert
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], $result);
    }

    public function test_get_multiple_returns_null_for_missing_keys(): void
    {
        // Arrange
        $this->storage->set('key1', 'value1');

        // Act
        $result = $this->storage->getMultiple(['key1', 'key2', 'key3']);

        // Assert
        $this->assertSame([
            'key1' => 'value1',
            'key2' => null,
            'key3' => null,
        ], $result);
    }

    public function test_set_multiple(): void
    {
        // Arrange
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // Act
        $this->storage->setMultiple($items);

        // Assert
        $this->assertSame('value1', $this->storage->get('key1'));
        $this->assertSame('value2', $this->storage->get('key2'));
        $this->assertSame('value3', $this->storage->get('key3'));
    }

    public function test_delete_multiple(): void
    {
        // Arrange
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->storage->set('key3', 'value3');

        // Act
        $this->storage->deleteMultiple(['key1', 'key2']);

        // Assert
        $this->assertFalse($this->storage->exists('key1'));
        $this->assertFalse($this->storage->exists('key2'));
        $this->assertTrue($this->storage->exists('key3'));
    }

    public function test_exists_returns_true_for_existing_key(): void
    {
        // Arrange
        $this->storage->set('existing_key', 'value');

        // Act
        $result = $this->storage->exists('existing_key');

        // Assert
        $this->assertTrue($result);
    }

    public function test_exists_returns_false_for_non_existent_key(): void
    {
        // Act
        $result = $this->storage->exists('non_existent_key');

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_removes_existing_key(): void
    {
        // Arrange
        $key = 'key_to_delete';
        $this->storage->set($key, 'value');

        // Act
        $result = $this->storage->delete($key);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists($key));
    }

    public function test_delete_returns_false_for_non_existent_key(): void
    {
        // Act
        $result = $this->storage->delete('non_existent_key');

        // Assert
        $this->assertFalse($result);
    }

    public function test_clear_removes_all_data(): void
    {
        // Arrange
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->storage->set('key3', 'value3');

        // Act
        $this->storage->clear();

        // Assert
        $this->assertFalse($this->storage->exists('key1'));
        $this->assertFalse($this->storage->exists('key2'));
        $this->assertFalse($this->storage->exists('key3'));
    }

    public function test_set_expires(): void
    {
        // Arrange
        $newExpires = time() + 7200;

        // Act
        $this->storage->setExpires($newExpires);

        // Assert
        $this->assertSame($newExpires, $this->storage->getExpires());
    }

    public function test_set_domain(): void
    {
        // Arrange
        $domain = 'example.com';

        // Act
        $this->storage->setDomain($domain);

        // Assert
        $this->assertSame($domain, $this->storage->getDomain());
    }

    public function test_set_path(): void
    {
        // Arrange
        $path = '/admin';

        // Act
        $this->storage->setPath($path);

        // Assert
        $this->assertSame($path, $this->storage->getPath());
    }

    public function test_set_secure(): void
    {
        // Act
        $this->storage->setSecure(true);

        // Assert
        $this->assertTrue($this->storage->isSecure());
    }

    public function test_set_http_only(): void
    {
        // Act
        $this->storage->setHttpOnly(false);

        // Assert
        $this->assertFalse($this->storage->isHttpOnly());
    }

    public function test_set_same_site(): void
    {
        // Act
        $this->storage->setSameSite('Strict');

        // Assert
        $this->assertSame('Strict', $this->storage->getSameSite());
    }

    public function test_set_same_site_invalid_throws_exception(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->storage->setSameSite('Invalid');
    }

    public function test_get_all(): void
    {
        // Arrange
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // Act
        $this->storage->setMultiple($data);
        $result = $this->storage->getAll();

        // Assert
        $this->assertSame($data, $result);
    }

    public function test_is_empty(): void
    {
        // Assert
        $this->assertTrue($this->storage->isEmpty());

        // Act
        $this->storage->set('key', 'value');

        // Assert
        $this->assertFalse($this->storage->isEmpty());

        $this->storage->clear();
    }

    public function test_prefix_isolation(): void
    {
        // Arrange
        $storage1 = new CookieStorage('app1_');
        $storage2 = new CookieStorage('app2_');

        // Act
        $storage1->set('shared_key', 'value1');
        $storage2->set('shared_key', 'value2');

        // Assert
        $this->assertSame('value1', $storage1->get('shared_key'));
        $this->assertSame('value2', $storage2->get('shared_key'));

        $storage1->clear();
        $storage2->clear();
    }

    public function test_stores_string_value(): void
    {
        // Arrange
        $key = 'string_key';
        $value = 'Hello World';

        // Act
        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        // Assert
        $this->assertIsString($result);
        $this->assertSame($value, $result);
    }

    public function test_stores_array_value(): void
    {
        // Arrange
        $key = 'array_key';
        $value = ['a' => 1, 'b' => 2, 'c' => 3];

        // Act
        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        // Assert
        $this->assertIsArray($result);
        $this->assertSame($value, $result);
    }

    public function test_stores_boolean_value(): void
    {
        // Arrange
        $key = 'bool_key';
        $value = true;

        // Act
        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        // Assert
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function test_stores_null_value(): void
    {
        // Arrange
        $key = 'null_key';
        $value = null;

        // Act
        $this->storage->set($key, $value);
        $result = $this->storage->get($key);

        // Assert
        $this->assertNull($result);
        $this->assertTrue($this->storage->exists($key));
    }

    public function test_set_multiple_with_config(): void
    {
        // Arrange
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // Act
        $this->storage->setMultipleWithConfig($items);

        // Assert
        $this->assertSame('value1', $this->storage->get('key1'));
        $this->assertSame('value2', $this->storage->get('key2'));
        $this->assertSame('value3', $this->storage->get('key3'));

        $this->storage->clear();
    }
}
