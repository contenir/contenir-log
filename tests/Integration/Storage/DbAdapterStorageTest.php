<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Integration\Storage;

use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Tests\TestAsset\LogRecordFactory;
use Contenir\Log\Tests\TestAsset\SqliteLogDatabase;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[RequiresPhpExtension('pdo_sqlite')]
final class DbAdapterStorageTest extends TestCase
{
    #[Test]
    public function honoursACustomTableAndColumnMapIncludingLevelAndTimestamp(): void
    {
        $adapter = SqliteLogDatabase::adapter();
        $adapter->query(
            'CREATE TABLE audit (msg TEXT, trace TEXT, lvl INTEGER, lvl_name TEXT, psr_level TEXT, logged_at TEXT)',
            Adapter::QUERY_MODE_EXECUTE,
        );

        $storage = new DbAdapterStorage($adapter, 'audit', [
            'message'      => 'msg',
            'error'        => 'trace',
            'priority'     => 'lvl',
            'priorityName' => 'lvl_name',
            'level'        => 'psr_level',
            'createdAt'    => 'logged_at',
        ]);
        $storage->store(LogRecordFactory::error(error: null));

        static::assertSame(
            [[
                'msg'       => 'something broke',
                'trace'     => null,
                'lvl'       => '3',
                'lvl_name'  => 'ERR',
                'psr_level' => 'error',
                'logged_at' => '2026-05-27 10:00:00',
            ]],
            SqliteLogDatabase::rows($adapter, 'audit'),
        );
    }

    #[Test]
    public function ignoresMappedFieldsThatARecordDoesNotHave(): void
    {
        $adapter = SqliteLogDatabase::adapter();
        $adapter->query('CREATE TABLE log (message TEXT)', Adapter::QUERY_MODE_EXECUTE);

        (new DbAdapterStorage($adapter, 'log', ['message' => 'message', 'channel' => 'channel']))->store(
            LogRecordFactory::error(),
        );

        static::assertSame([['message' => 'something broke']], SqliteLogDatabase::rows($adapter));
    }

    #[Test]
    public function insertsTheDefaultColumnsIntoTheLogTable(): void
    {
        $adapter = SqliteLogDatabase::create();

        (new DbAdapterStorage($adapter))->store(LogRecordFactory::error());

        static::assertSame(
            [[
                'message'      => 'something broke',
                'error'        => "RuntimeException: boom in /app.php:10\n#0 {main}",
                'priority'     => '3',
                'priorityName' => 'ERR',
            ]],
            SqliteLogDatabase::rows($adapter, columns: 'message, error, priority, priorityName'),
        );
    }

    #[Test]
    public function routesPresentContextEntriesToTheirColumns(): void
    {
        $adapter = SqliteLogDatabase::adapter();
        $adapter->query('CREATE TABLE log (message TEXT, student_id INTEGER)', Adapter::QUERY_MODE_EXECUTE);

        $storage = new DbAdapterStorage($adapter, 'log', ['message' => 'message'], ['student' => 'student_id']);
        $storage->store(LogRecordFactory::error(
            message: 'with student',
            context: ['student' => 42],
        ));
        $storage->store(LogRecordFactory::error(message: 'without student'));

        static::assertSame(
            [
                ['message' => 'with student', 'student_id' => '42'],
                ['message' => 'without student', 'student_id' => null],
            ],
            SqliteLogDatabase::rows($adapter),
        );
    }
}
