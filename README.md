# contenir/contenir-log

[![Continuous Integration](https://github.com/contenir/contenir-log/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-log/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-log/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-log)

A small PSR-3 logger for Laminas MVC and Mezzio with pluggable storage. Ships
with **filesystem** and **database** backends; add your own by implementing
`Contenir\Log\Storage\StorageInterface`.

When a `Throwable` is passed in the PSR-3 context under `exception`, its message
chain and full stack trace are recorded (the `error` column, or a multi-line
file entry), so a logged 500 carries everything you need to debug it.

## Requirements

- PHP 8.3, 8.4 or 8.5
- `php-db/phpdb` 0.6.x (used by the database backend), plus the driver
  package for your database, e.g. `php-db/phpdb-sqlite`
- `psr/log` ^1.0, ^2.0 or ^3.0
- `psr/container` ^1.0 or ^2.0

The 0.x releases, which support PHP 8.1, remain available from the `0.x`
branch and `v0.*` tags; see [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Install

```bash
composer require contenir/contenir-log
```

- **Laminas MVC**: register the `Contenir\Log` module (laminas-component-installer
  offers this automatically). `Contenir\Log\Module::getConfig()` exposes the
  services under `service_manager`.
- **Mezzio**: add `Contenir\Log\ConfigProvider` to your config aggregator. It
  exposes the services under `dependencies`.

Both register:

| Service | Built by |
| --- | --- |
| `Contenir\Log\Logger` | `Contenir\Log\Factory\LoggerFactory` |
| `Contenir\Log\Storage\FilesystemStorage` (alias `filesystem`) | `Contenir\Log\Storage\Factory\FilesystemStorageFactory` |
| `Contenir\Log\Storage\DbAdapterStorage` (alias `db`) | `Contenir\Log\Storage\Factory\DbAdapterStorageFactory` |

## Configuration

Override the `log` config key. The defaults are:

```php
return [
    'log' => [
        'storage' => [
            'adapter' => 'filesystem',
            'options' => [
                'path' => 'data/log/app.log',
            ],
        ],
    ],
];
```

`log.storage.adapter` is a service id resolved through the container: `'db'` and
`'filesystem'` use the package's aliases, and you can register your own
`StorageInterface` and name its service id here instead. If the key is missing,
the `FilesystemStorage` service is used. A service that is not a
`StorageInterface` makes `LoggerFactory` throw a `RuntimeException`.

### Filesystem storage

```php
'log' => [
    'storage' => [
        'adapter' => 'filesystem',
        'options' => [
            'path' => 'data/log/app.log', // default
        ],
    ],
],
```

The parent directory is created on demand (mode `0775`). Each record is
appended under an exclusive lock, so concurrent workers don't interleave lines:

```text
[2026-05-27 10:00:00] ERR (3): HTTP 500 at /checkout
RuntimeException: boom in /app/src/Handler.php:42
#0 {main}
```

`store()` throws a `RuntimeException` when the directory cannot be created or
the file cannot be written. It never raises PHP warnings, and losing a race with
another worker creating the same directory is not an error.

### Database storage

```php
'log' => [
    'storage' => [
        'adapter' => 'db',
        'options' => [
            // Service id of a PhpDb\Adapter\AdapterInterface.
            'adapter' => PhpDb\Adapter\Adapter::class, // default
            'table'   => 'log',                        // default

            // LogRecord field => table column. Only mapped fields are written,
            // so a createdAt column with a database default can be left out.
            // Available fields: message, error, priority, priorityName, level,
            // createdAt (formatted Y-m-d H:i:s).
            'columns' => [ // default
                'message'      => 'message',
                'error'        => 'error',
                'priority'     => 'priority',
                'priorityName' => 'priorityName',
            ],

            // Optional: route individual PSR-3 context entries to their own
            // columns. Only keys present on a given record are written.
            'context' => [
                'student' => 'student_id',
            ],
        ],
    ],
],
```

Non-string `adapter` or `table` values fall back to the defaults. A `columns` or
`context` map that is not string-to-string, or an adapter service that is not an
`AdapterInterface`, makes `DbAdapterStorageFactory` throw a `RuntimeException`.

A matching MySQL table:

```sql
CREATE TABLE log (
    log_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message      TEXT NOT NULL,
    error        MEDIUMTEXT NULL,
    priority     TINYINT UNSIGNED NOT NULL,
    priorityName VARCHAR(10) NOT NULL,
    createdAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

## Usage

Pull `Contenir\Log\Logger` from the container. It is a `Psr\Log\LoggerInterface`:

```php
$logger->error('HTTP 500 at {uri}', ['uri' => $uri, 'exception' => $e]);
```

`{uri}` is a PSR-3 placeholder: any `{key}` in the message is replaced by the
matching context entry when that entry is a scalar or `Stringable`, so the line
above is recorded as `HTTP 500 at /checkout`. Other values (arrays, plain
objects, `null`) leave the placeholder as written.

`exception` is special-cased: it is **not** interpolated into the message. A
`Throwable` passed there has its message chain (joined by `Caused by`) and the
outermost stack trace captured separately, keeping the message clean while
preserving everything needed to debug. A non-`Throwable` value under
`exception` is ignored.

The whole context, `exception` included, is kept on the record, so a custom
storage can use it.

### Levels and priorities

`priority` and `priorityName` follow Laminas\Log's numeric scheme, so existing
log tables built for it remain compatible:

| PSR-3 level | priority | priorityName |
| --- | --- | --- |
| emergency | 0 | EMERG |
| alert | 1 | ALERT |
| critical | 2 | CRIT |
| error | 3 | ERR |
| warning | 4 | WARN |
| notice | 5 | NOTICE |
| info | 6 | INFO |
| debug | 7 | DEBUG |

Any other level is recorded at priority 7 under its own name. `Stringable`
levels are cast to string; non-scalar levels are recorded as an empty string.

### Using the logger without a container

```php
use Contenir\Log\Logger;
use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\FilesystemStorage;

$logger = new Logger(new FilesystemStorage('/var/log/site/app.log'));

$logger = new Logger(new DbAdapterStorage(
    $adapter,                              // PhpDb\Adapter\AdapterInterface
    'audit',                               // table
    ['message' => 'msg', 'error' => 'trace'],
    ['student' => 'student_id'],
));
```

### Custom storage

Implement `StorageInterface` and register it as a service:

```php
use Contenir\Log\LogRecord;
use Contenir\Log\Storage\StorageInterface;

final class SlackStorage implements StorageInterface
{
    public function store(LogRecord $record): void
    {
        // $record->level        PSR-3 level, e.g. "error"
        // $record->priority     0-7
        // $record->priorityName e.g. "ERR"
        // $record->message      interpolated message
        // $record->error        formatted exception, or null
        // $record->context      the PSR-3 context as passed
        // $record->createdAt    DateTimeImmutable
    }
}
```

```php
'log' => ['storage' => ['adapter' => SlackStorage::class]],
```

`store()` should throw a `RuntimeException` when the record cannot be persisted.

## Development

The QA toolchain is [contenir/contenir-qa-tools](https://github.com/contenir/contenir-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed
separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: no I/O, collaborators doubled
composer test-integration  # integration suite: real filesystem and in-memory SQLite
composer test-coverage     # both suites, clover.xml for Codecov
composer mutation-test     # Infection mutation testing over both suites
```

The integration suite needs the `pdo_sqlite` extension for the database tests.

## License

MIT. See [LICENSE](LICENSE).
