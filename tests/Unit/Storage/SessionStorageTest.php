<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Tests\Unit\Storage;

use AndyDefer\StorageKit\Storage\SessionStorage;
use PHPUnit\Framework\TestCase;

final class SessionStorageTest extends TestCase
{
    private SessionStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->storage = new SessionStorage('test_namespace');
        $this->storage->clear();
    }

    protected function tearDown(): void
    {
        $this->storage->clear();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

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

    public function test_get_namespace(): void
    {
        // Arrange
        $namespace = 'custom_namespace';
        $storage = new SessionStorage($namespace);

        // Act
        $result = $storage->getNamespace();

        // Assert
        $this->assertSame($namespace, $result);
        $storage->clear();
    }

    public function test_set_namespace(): void
    {
        // Arrange
        $oldNamespace = 'old_namespace';
        $newNamespace = 'new_namespace';

        $storage = new SessionStorage($oldNamespace);
        $storage->set('test_key', 'test_value');

        // Act
        $storage->setNamespace($newNamespace);

        // Assert
        $this->assertSame($newNamespace, $storage->getNamespace());

        // Les données doivent être dans le nouveau namespace
        $this->assertSame('test_value', $storage->get('test_key'));

        $storage->clear();
    }

    public function test_is_session_active(): void
    {
        // Arrange
        $storage = new SessionStorage('test');

        // Assert
        $this->assertTrue($storage->isSessionActive());
        $storage->clear();
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

    public function test_namespace_isolation(): void
    {
        // Arrange
        $storage1 = new SessionStorage('namespace1');
        $storage2 = new SessionStorage('namespace2');

        // Act
        $storage1->set('shared_key', 'value1');
        $storage2->set('shared_key', 'value2');

        // Assert
        $this->assertSame('value1', $storage1->get('shared_key'));
        $this->assertSame('value2', $storage2->get('shared_key'));

        $storage1->clear();
        $storage2->clear();
    }

    public function test_session_required_exception(): void
    {
        // Arrange
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session must be started before using SessionStorage');

        // Act
        new SessionStorage('test');
    }
}
