<?php

declare(strict_types=1);

namespace Contenir\Log\Storage\Factory;

use Contenir\Log\Storage\DbAdapterStorage;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;
use function is_string;
use function sprintf;

final class DbAdapterStorageFactory
{
    public function __invoke(ContainerInterface $container): DbAdapterStorage
    {
        $config     = $container->has('config') ? $container->get('config') : [];
        $config     = is_array($config) ? $config : [];
        $log        = $config['log'] ?? null;
        $log        = is_array($log) ? $log : [];
        $storageCfg = $log['storage'] ?? null;
        $storageCfg = is_array($storageCfg) ? $storageCfg : [];
        $options    = $storageCfg['options'] ?? null;
        $options    = is_array($options) ? $options : [];

        $adapterName    = $options['adapter'] ?? null;
        $adapterService = is_string($adapterName) ? $adapterName : Adapter::class;
        $adapter        = $container->get($adapterService);
        if (! $adapter instanceof AdapterInterface) {
            throw new RuntimeException(sprintf(
                'contenir/contenir-log: db adapter service "%s" must implement %s.',
                $adapterService,
                AdapterInterface::class,
            ));
        }

        $tableName = $options['table'] ?? null;
        $table     = is_string($tableName) ? $tableName : 'log';

        return new DbAdapterStorage(
            $adapter,
            $table,
            $this->resolveStringMap($options['columns'] ?? null, DbAdapterStorage::DEFAULT_COLUMNS, 'columns'),
            $this->resolveStringMap($options['context'] ?? null, [], 'context'),
        );
    }

    /**
     * @param array<string, string> $default
     * @return array<string, string>
     * @throws RuntimeException If the configured map is not string-to-string.
     */
    private function resolveStringMap(mixed $configured, array $default, string $optionName): array
    {
        if (! is_array($configured)) {
            return $default;
        }

        $map = [];
        foreach ($configured as $key => $column) {
            if (! is_string($key) || ! is_string($column)) {
                throw new RuntimeException(sprintf(
                    'contenir/contenir-log: log storage "%s" must map string keys to string column names.',
                    $optionName,
                ));
            }

            $map[$key] = $column;
        }

        return $map;
    }
}
