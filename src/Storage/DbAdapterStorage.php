<?php

declare(strict_types=1);

namespace Contenir\Log\Storage;

use Contenir\Log\LogRecord;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

use function array_key_exists;

/**
 * Inserts each record into a database table via a Laminas DB adapter.
 *
 * The column map translates LogRecord fields to table columns, so sites with
 * differently-named log tables can remap without code changes. Fields omitted
 * from the map are not written (e.g. a `createdAt` column with a DB default is
 * left for the database to populate).
 *
 * The context map additionally routes individual PSR-3 context entries to their
 * own columns (e.g. `['student' => 'student_id']`), so domain identifiers passed
 * alongside a message land in dedicated, indexable columns. Only context keys
 * actually present on the record are written.
 */
final class DbAdapterStorage implements StorageInterface
{
    public const DEFAULT_COLUMNS = [
        'message'      => 'message',
        'error'        => 'error',
        'priority'     => 'priority',
        'priorityName' => 'priorityName',
    ];

    /**
     * @param array<string, string> $columns        LogRecord field => table column.
     * @param array<string, string> $contextColumns Context key => table column.
     */
    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly string $table = 'log',
        private readonly array $columns = self::DEFAULT_COLUMNS,
        private readonly array $contextColumns = [],
    ) {
    }

    public function store(LogRecord $record): void
    {
        $fields = [
            'message'      => $record->message,
            'error'        => $record->error,
            'priority'     => $record->priority,
            'priorityName' => $record->priorityName,
            'level'        => $record->level,
            'createdAt'    => $record->createdAt->format('Y-m-d H:i:s'),
        ];

        $values = [];
        foreach ($this->columns as $field => $column) {
            if (array_key_exists($field, $fields)) {
                $values[$column] = $fields[$field];
            }
        }

        foreach ($this->contextColumns as $contextKey => $column) {
            if (array_key_exists($contextKey, $record->context)) {
                $values[$column] = $record->context[$contextKey];
            }
        }

        $sql    = new Sql($this->adapter);
        $insert = $sql->insert($this->table)->values($values);
        $sql->prepareStatementForSqlObject($insert)->execute();
    }
}
