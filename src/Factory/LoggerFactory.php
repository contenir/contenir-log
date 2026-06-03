<?php

declare(strict_types=1);

namespace Contenir\Log\Factory;

use Contenir\Log\Logger;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Storage\StorageInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;
use function is_string;
use function sprintf;

final class LoggerFactory
{
    public function __invoke(ContainerInterface $container): Logger
    {
        $config      = $container->has('config') ? $container->get('config') : [];
        $config      = is_array($config) ? $config : [];
        $log         = $config['log'] ?? null;
        $log         = is_array($log) ? $log : [];
        $storageCfg  = $log['storage'] ?? null;
        $storageCfg  = is_array($storageCfg) ? $storageCfg : [];
        $adapterName = $storageCfg['adapter'] ?? null;
        $adapter     = is_string($adapterName) ? $adapterName : FilesystemStorage::class;

        $storage = $container->get($adapter);
        if (! $storage instanceof StorageInterface) {
            throw new RuntimeException(sprintf(
                'contenir/contenir-log: storage adapter "%s" must resolve to a %s.',
                $adapter,
                StorageInterface::class,
            ));
        }

        return new Logger($storage);
    }
}
