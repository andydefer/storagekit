<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Tests\Unit\Storage;

use AndyDefer\StorageKit\Records\SqliteStorageStatsRecord;
use AndyDefer\StorageKit\Storage\SqliteStorage;
use PHPUnit\Framework\TestCase;

final class SqliteStorageTest extends TestCase
{
    private SqliteStorage $storage;

    private string $tempDbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDbPath = sys_get_temp_dir().'/sqlite_test_'.uniqid().'.db';
        $this->storage = new SqliteStorage($this->tempDbPath, 'test_kv');
    }

    protected function tearDown(): void
    {
        $this->storage->close();

        if (file_exists($this->tempDbPath)) {
            unlink($this->tempDbPath);
        }

        parent::tearDown();
    }

    public function test_constructor_creates_table(): void
    {
        // Assert - table should exist
        $stats = $this->storage->getStats();
        $this->assertSame('test_kv', $stats->table_name);
        $this->assertSame($this->tempDbPath, $stats->database_path);
        $this->assertFalse($stats->is_memory);
        $this->assertSame(0, $stats->total_items);
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

    public function test_get_returns_null_when_key_not_found(): void
    {
        // Act
        $result = $this->storage->get('non_existent');

        // Assert
        $this->assertNull($result);
    }

    public function test_get_returns_default_when_key_not_found(): void
    {
        // Act
        $result = $this->storage->get('non_existent', 'default');

        // Assert
        $this->assertSame('default', $result);
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
        $this->assertNull($this->storage->get('key1'));
        $this->assertNull($this->storage->get('key2'));
        $this->assertSame('value3', $this->storage->get('key3'));
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
        $this->assertNull($this->storage->get($key));
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
        $this->assertNull($this->storage->get('key1'));
        $this->assertNull($this->storage->get('key2'));
        $this->assertNull($this->storage->get('key3'));
    }

    public function test_count_returns_number_of_items(): void
    {
        // Arrange
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');

        // Act
        $count = $this->storage->count();

        // Assert
        $this->assertSame(2, $count);
    }

    public function test_get_database_size(): void
    {
        // Arrange
        $this->storage->set('key1', str_repeat('x', 1000));
        $this->storage->set('key2', str_repeat('y', 2000));

        // Act
        $size = $this->storage->getDatabaseSize();

        // Assert
        $this->assertGreaterThan(0, $size);
    }

    public function test_get_stats(): void
    {
        // Arrange
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->storage->get('key1');
        $this->storage->delete('key2');

        // Act
        $stats = $this->storage->getStats();

        // Assert
        $this->assertInstanceOf(SqliteStorageStatsRecord::class, $stats);
        $this->assertSame(1, $stats->total_items);
        $this->assertSame($this->tempDbPath, $stats->database_path);
        $this->assertSame('test_kv', $stats->table_name);
        $this->assertFalse($stats->is_memory);
        $this->assertSame(0, $stats->active_transactions);
        $this->assertGreaterThan(0, $stats->write_count);
        $this->assertGreaterThan(0, $stats->read_count);
    }

    public function test_transaction_commit(): void
    {
        // Arrange
        $this->storage->beginTransaction();
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');

        // Act
        $this->storage->commit();

        // Assert
        $this->assertSame('value1', $this->storage->get('key1'));
        $this->assertSame('value2', $this->storage->get('key2'));
    }

    public function test_transaction_rollback(): void
    {
        // Arrange
        $this->storage->beginTransaction();
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');

        // Act
        $this->storage->rollback();

        // Assert
        $this->assertNull($this->storage->get('key1'));
        $this->assertNull($this->storage->get('key2'));
    }

    public function test_nested_transactions(): void
    {
        // Arrange
        $this->storage->beginTransaction();
        $this->storage->set('key1', 'value1');

        $this->storage->beginTransaction(); // Nested
        $this->storage->set('key2', 'value2');

        $this->storage->commit(); // Commit inner

        // Act
        $this->storage->commit(); // Commit outer

        // Assert
        $this->assertSame('value1', $this->storage->get('key1'));
        $this->assertSame('value2', $this->storage->get('key2'));
    }

    public function test_in_transaction(): void
    {
        // Assert - initial
        $this->assertFalse($this->storage->inTransaction());

        // Act
        $this->storage->beginTransaction();

        // Assert
        $this->assertTrue($this->storage->inTransaction());

        // Act
        $this->storage->commit();

        // Assert
        $this->assertFalse($this->storage->inTransaction());
    }

    public function test_vacuum(): void
    {
        // Arrange
        $this->storage->set('key1', str_repeat('x', 5000));
        $this->storage->set('key2', str_repeat('y', 5000));
        $this->storage->delete('key1');

        // Act
        $result = $this->storage->vacuum();

        // Assert
        $this->assertTrue($result);
    }

    public function test_in_memory_storage(): void
    {
        // Arrange
        $storage = new SqliteStorage(':memory:', 'mem_test');

        // Act
        $storage->set('key', 'value');
        $stats = $storage->getStats();

        // Assert
        $this->assertTrue($storage->isMemoryDatabase());
        $this->assertSame(':memory:', $storage->getDatabasePath());
        $this->assertSame('value', $storage->get('key'));
        $this->assertSame(0, $stats->database_size);

        $storage->clear();
        $storage->close();
    }

    public function test_stores_multiple_data_types(): void
    {
        // Arrange
        $dataTypes = [
            'string' => 'Hello World',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => ['a' => 1, 'b' => 2],
            'nested' => ['user' => ['name' => 'John', 'age' => 30]],
        ];

        // Act & Assert
        foreach ($dataTypes as $key => $value) {
            $this->storage->set($key, $value);
            $result = $this->storage->get($key);
            $this->assertSame($value, $result);
        }
    }

    public function test_close_connection(): void
    {
        // Act
        $result = $this->storage->close();

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_connection(): void
    {
        // Act
        $connection = $this->storage->getConnection();

        // Assert
        $this->assertInstanceOf(\SQLite3::class, $connection);
    }
}
