<?php

declare(strict_types=1);

namespace Contenir\Log\Storage\Factory;

use Contenir\Log\Storage\DbAdapterStorage;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;
use function sprintf;

final class DbAdapterStorageFactory
{
    public function __invoke(ContainerInterface $container): DbAdapterStorage
    {
        $config = $container->has('config') ? (array) $container->get('config') : [];
        $db     = (array) ($config['contenir_log']['db'] ?? []);

        $adapterService = (string) ($db['adapter'] ?? Adapter::class);
        $adapter        = $container->get($adapterService);
        if (! $adapter instanceof AdapterInterface) {
            throw new RuntimeException(sprintf(
                'contenir/contenir-log: db adapter service "%s" must implement %s.',
                $adapterService,
                AdapterInterface::class,
            ));
        }

        $table   = (string) ($db['table'] ?? 'log');
        $columns = is_array($db['columns'] ?? null) ? $db['columns'] : DbAdapterStorage::DEFAULT_COLUMNS;

        return new DbAdapterStorage($adapter, $table, $columns);
    }
}
