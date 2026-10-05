# Upgrading from 0.x to 2.0

2.0 has the same public API as 0.1.4. The platform requirement changes, and
one behaviour is fixed.

| | 0.x | 2.0 |
| --- | --- | --- |
| PHP | ^8.1 | 8.3, 8.4 or 8.5 |
| laminas/laminas-db | ^2.17 | ^2.19 |
| psr/log | ^1.0 \|\| ^2.0 \|\| ^3.0 | unchanged |
| psr/container | ^1.0 \|\| ^2.0 | unchanged |

To upgrade, update the constraint:

```bash
composer require contenir/contenir-log:^2.0
```

No code changes are needed. `Logger`, `LogRecord`, `Storage\StorageInterface`,
`Storage\FilesystemStorage`, `Storage\DbAdapterStorage`, the three factories,
`ConfigProvider`, `Module` and the `log` configuration keep their signatures
and behaviour.

## Behaviour change: FilesystemStorage no longer raises warnings

In 0.x, `FilesystemStorage::store()` let PHP warnings from `mkdir()` and
`file_put_contents()` escape. With an error handler that turns warnings into
exceptions installed, a failed write surfaced as an `ErrorException`, and
losing a directory-creation race to another worker failed the log call even
though the directory then existed.

Before (0.x), with a warnings-to-exceptions handler:

```php
try {
    $logger->error('HTTP 500');
} catch (ErrorException $e) {
    // "mkdir(): File exists", or "file_put_contents(...): Failed to open stream"
}
```

After (2.0):

```php
try {
    $logger->error('HTTP 500');
} catch (RuntimeException $e) {
    // contenir/contenir-log: cannot write to log file "data/log/app.log".
}
```

A lost directory race is no longer an error at all. If you caught
`ErrorException` around log calls, catch `RuntimeException` instead.

## Typed constant

`Storage\DbAdapterStorage::DEFAULT_COLUMNS` is declared `public const array`.
The class is `final`, so nothing can redeclare it; reading it is unchanged.

Projects that must stay on PHP 8.1 or 8.2 can keep using `^0.1`, which is
maintained on the `0.x` branch.
