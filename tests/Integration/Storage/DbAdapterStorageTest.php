<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Integration\Storage;

use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Tests\TestAsset\LogRecordFactory;
use Contenir\Log\Tests\TestAsset\SqliteLogDatabase;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

#[Group('integration')]
final class DbAdapterStorageTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is not available');
        }
    }

    public function testStoreInsertsMappedColumnsIntoLogTable(): void
    {
        $adapter = SqliteLogDatabase::create();

        (new DbAdapterStorage($adapter))->store(LogRecordFactory::error());

        $rows = SqliteLogDatabase::rows($adapter);
        self::assertCount(1, $rows);
        self::assertSame('something broke', $rows[0]['message']);
        self::assertStringContainsString('RuntimeException: boom', (string) $rows[0]['error']);
        self::assertSame('3', (string) $rows[0]['priority']);
        self::assertSame('ERR', $rows[0]['priorityName']);
    }

    public function testStoreHonoursCustomColumnMap(): void
    {
        $adapter = SqliteLogDatabase::adapter();
        $adapter->query(
            'CREATE TABLE audit ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, msg TEXT, trace TEXT, lvl INTEGER, lvl_name TEXT)',
            Adapter::QUERY_MODE_EXECUTE,
        );

        $storage = new DbAdapterStorage($adapter, 'audit', [
            'message'      => 'msg',
            'error'        => 'trace',
            'priority'     => 'lvl',
            'priorityName' => 'lvl_name',
        ]);
        $storage->store(LogRecordFactory::error());

        $rows = SqliteLogDatabase::rows($adapter, 'audit');
        self::assertCount(1, $rows);
        self::assertSame('something broke', $rows[0]['msg']);
        self::assertStringContainsString('RuntimeException: boom', (string) $rows[0]['trace']);
        self::assertSame('3', (string) $rows[0]['lvl']);
        self::assertSame('ERR', $rows[0]['lvl_name']);
    }

    public function testStoreRoutesContextEntriesToColumns(): void
    {
        $adapter = SqliteLogDatabase::adapter();
        $adapter->query(
            'CREATE TABLE log ('
            . 'log_id INTEGER PRIMARY KEY AUTOINCREMENT, message TEXT, student_id INTEGER)',
            Adapter::QUERY_MODE_EXECUTE,
        );

        $storage = new DbAdapterStorage($adapter, 'log', ['message' => 'message'], ['student' => 'student_id']);
        $storage->store(LogRecordFactory::error(context: ['student' => 42]));
        $storage->store(LogRecordFactory::error());

        $rows = SqliteLogDatabase::rows($adapter);
        self::assertCount(2, $rows);
        self::assertSame('42', (string) $rows[0]['student_id']);
        self::assertNull($rows[1]['student_id']);
    }
}
