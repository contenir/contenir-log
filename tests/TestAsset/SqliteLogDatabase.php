<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\TestAsset;

use PhpDb\Adapter\Adapter;
use PhpDb\Sqlite\AdapterPlatform;
use PhpDb\Sqlite\Pdo\Connection;
use PhpDb\Sqlite\Pdo\Driver;
use PhpDb\Sqlite\Pdo\Feature\SqliteRowCounter;

use function is_scalar;

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
        $driver = new Driver(
            new Connection(['dsn' => 'sqlite::memory:']),
            features: [new SqliteRowCounter()],
        );

        return new Adapter($driver, new AdapterPlatform($driver));
    }

    public static function create(): Adapter
    {
        $adapter = self::adapter();
        $adapter->executeQuery(
            'CREATE TABLE log ('
                . 'log_id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'message TEXT, '
                . 'error TEXT, '
                . 'priority INTEGER, '
                . 'priorityName TEXT, '
                . 'createdAt TEXT DEFAULT CURRENT_TIMESTAMP'
                . ')',
        );

        return $adapter;
    }

    /**
     * Every row in insertion order, with values as strings (or null), the way
     * PDO SQLite returns them.
     *
     * @return list<array<string, string|null>>
     */
    public static function rows(Adapter $adapter, string $table = 'log', string $columns = '*'): array
    {
        $result = $adapter->executeQuery("SELECT {$columns} FROM {$table} ORDER BY rowid");

        $rows = [];
        foreach ($result as $row) {
            $cells = [];
            foreach ((array) $row as $column => $value) {
                $cells[(string) $column] = is_scalar($value) ? (string) $value : null;
            }

            $rows[] = $cells;
        }

        return $rows;
    }
}
