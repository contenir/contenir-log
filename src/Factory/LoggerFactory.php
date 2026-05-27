<?php

declare(strict_types=1);

namespace Contenir\Log\Factory;

use Contenir\Log\Logger;
use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Storage\StorageInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function array_keys;
use function implode;
use function sprintf;

final class LoggerFactory
{
    private const STORAGES = [
        'filesystem' => FilesystemStorage::class,
        'db'         => DbAdapterStorage::class,
    ];

    public function __invoke(ContainerInterface $container): Logger
    {
        $config = $container->has('config') ? (array) $container->get('config') : [];
        $driver = (string) ($config['contenir_log']['storage'] ?? 'filesystem');

        $service = self::STORAGES[$driver] ?? null;
        if ($service === null) {
            throw new RuntimeException(sprintf(
                'contenir/contenir-log: unknown storage "%s"; expected one of: %s.',
                $driver,
                implode(', ', array_keys(self::STORAGES)),
            ));
        }

        $storage = $container->get($service);
        if (! $storage instanceof StorageInterface) {
            throw new RuntimeException(sprintf(
                'contenir/contenir-log: storage service "%s" must implement %s.',
                $service,
                StorageInterface::class,
            ));
        }

        return new Logger($storage);
    }
}
