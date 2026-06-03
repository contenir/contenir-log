# contenir/contenir-log

A small PSR-3 logger for Laminas MVC and Mezzio with pluggable storage. Ships
with **filesystem** and **database** backends; add your own by implementing
`Contenir\Log\Storage\StorageInterface`.

When a `Throwable` is passed in the PSR-3 context under `exception`, its message
chain and full stack trace are recorded (the `error` column / a multi-line file
entry) — so a logged 500 carries everything you need to debug it.

## Install

```bash
composer require contenir/contenir-log
```

- **Laminas MVC**: register the `Contenir\Log` module (laminas-component-installer
  offers this automatically).
- **Mezzio**: add `Contenir\Log\ConfigProvider` to your config aggregator.

## Configure

Override the `log` config key:

```php
return [
    'log' => [
        'storage' => [
            // 'filesystem' (default) or 'db' — both are registered aliases.
            // You can also name any service id implementing StorageInterface.
            'adapter' => 'db',

            // Options for the chosen adapter:
            'options' => [
                // db:
                'adapter' => Laminas\Db\Adapter\Adapter::class, // db adapter service id
                'table'   => 'log',
                // LogRecord field => table column (only mapped fields are written;
                // a createdAt column with a DB default can be left out).
                'columns' => [
                    'message'      => 'message',
                    'error'        => 'error',
                    'priority'     => 'priority',
                    'priorityName' => 'priorityName',
                ],
                // Optional: route individual PSR-3 context entries to their own
                // columns. Only keys present on a given record are written, so a
                // domain id like student maps straight to an indexable column.
                'context' => [
                    'student' => 'student_id',
                ],

                // filesystem:
                // 'path' => 'data/log/app.log',
            ],
        ],
    ],
];
```

`adapter` is resolved through the container, so `'db'` / `'filesystem'` use the
package's aliases, and you can register your own `StorageInterface` and name its
service id here instead.

## Use

Pull `Contenir\Log\Logger` from the container (it's a `Psr\Log\LoggerInterface`):

```php
$logger->error('HTTP 500 at {uri}', ['uri' => $uri, 'exception' => $e]);
```

`{uri}` is a PSR-3 placeholder: any `{key}` in the message is replaced by the
matching `context` entry, so the line above is recorded as `HTTP 500 at /checkout`.

`exception` is special-cased — it is **not** interpolated into the message.
A `Throwable` passed there has its message chain and full stack trace captured
separately (the `error` column, or a multi-line block in the file backend),
keeping the message clean while preserving everything needed to debug.

`priority` / `priorityName` follow Laminas\Log's numeric scheme, so existing log
tables built for it remain compatible.
