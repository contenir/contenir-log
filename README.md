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

Override the `contenir_log` config key:

```php
return [
    'contenir_log' => [
        // 'filesystem' (default) or 'db'
        'storage'    => 'db',

        'filesystem' => [
            'path' => 'data/log/app.log',
        ],

        'db' => [
            'adapter' => Laminas\Db\Adapter\Adapter::class, // adapter service id
            'table'   => 'log',
            // LogRecord field => table column (only mapped fields are written;
            // a createdAt column with a DB default can be left out).
            'columns' => [
                'message'      => 'message',
                'error'        => 'error',
                'priority'     => 'priority',
                'priorityName' => 'priorityName',
            ],
        ],
    ],
];
```

## Use

Pull `Contenir\Log\Logger` from the container (it's a `Psr\Log\LoggerInterface`):

```php
$logger->error('HTTP 500 at {uri}', ['uri' => $uri, 'exception' => $e]);
```

`priority` / `priorityName` follow Laminas\Log's numeric scheme, so existing log
tables built for it remain compatible.
