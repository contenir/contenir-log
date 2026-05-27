<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\TestAsset;

use Laminas\Db\Adapter\Adapter;

use function array_map;
use function iterator_to_array;

/**
 * Builds a throwaway in-memory SQLite adapter for exercising DbAdapterStorage
 * against a real database. `create()` includes a `log` table mirroring the
 * columns the storage writes; `adapter()` returns a bare adapter for tests that
 * need a differently-shaped table (e.g. custom column maps).
 */
final class SqliteLogDatabase
{
    public static function adapter(): Adapter
    {
        return new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => ':memory:',
        ]);
    }

    public static function create(): Adapter
    {
        $adapter = self::adapter();
        $adapter->query(
            'CREATE TABLE log ('
            . 'log_id INTEGER PRIMARY KEY AUTOINCREMENT, '
            . 'message TEXT, '
            . 'error TEXT, '
            . 'priority INTEGER, '
            . 'priorityName TEXT, '
            . 'createdAt TEXT DEFAULT CURRENT_TIMESTAMP'
            . ')',
            Adapter::QUERY_MODE_EXECUTE,
        );

        return $adapter;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function rows(Adapter $adapter, string $table = 'log'): array
    {
        $result = $adapter->query('SELECT * FROM ' . $table . ' ORDER BY 1', Adapter::QUERY_MODE_EXECUTE);

        return array_map(
            static fn ($row): array => (array) $row,
            iterator_to_array($result),
        );
    }
}
