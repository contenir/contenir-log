<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Storage;

use Contenir\Log\LogRecord;
use Contenir\Log\Storage\DbAdapterStorage;
use DateTimeImmutable;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

#[Group('unit')]
final class DbAdapterStorageTest extends TestCase
{
    public function testStoreInsertsMappedColumns(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is not available');
        }

        $adapter = new Adapter(['driver' => 'Pdo_Sqlite', 'database' => ':memory:']);
        $adapter->query(
            'CREATE TABLE log (log_id INTEGER PRIMARY KEY AUTOINCREMENT, message TEXT, error TEXT, '
            . 'priority INTEGER, priorityName TEXT, createdAt TEXT DEFAULT CURRENT_TIMESTAMP)',
            Adapter::QUERY_MODE_EXECUTE,
        );

        $storage = new DbAdapterStorage($adapter, 'log');
        $storage->store(new LogRecord(
            level: 'error',
            priority: 3,
            priorityName: 'ERR',
            message: 'db message',
            error: 'trace here',
            context: [],
            createdAt: new DateTimeImmutable(),
        ));

        $rows = [];
        foreach ($adapter->query('SELECT * FROM log', Adapter::QUERY_MODE_EXECUTE) as $row) {
            $rows[] = (array) $row;
        }

        self::assertCount(1, $rows);
        self::assertSame('db message', $rows[0]['message']);
        self::assertSame('trace here', $rows[0]['error']);
        self::assertSame('3', (string) $rows[0]['priority']);
        self::assertSame('ERR', $rows[0]['priorityName']);
    }
}
