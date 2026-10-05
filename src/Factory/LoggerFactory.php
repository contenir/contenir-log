<?php

declare(strict_types=1);

namespace Contenir\Log\Factory;

use Contenir\Log\Logger;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Storage\StorageInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Builds the Logger around the storage service named by `log.storage.adapter`,
 * falling back to {@see FilesystemStorage} when none is configured.
 *
 * @api
 */
final class LoggerFactory
{
    /**
     * @return array<array-key, mixed>
     *
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; each level is checked here.
     */
    private static function storageConfig(ContainerInterface $container): array
    {
        $value = $container->has('config') ? $container->get('config') : [];
        foreach (['log', 'storage'] as $key) {
            $value = is_array($value) ? $value[$key] ?? null : null;
        }

        return is_array($value) ? $value : [];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws RuntimeException When the adapter service is not a StorageInterface.
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; the type is checked here.
     */
    public function __invoke(ContainerInterface $container): Logger
    {
        $adapterName = self::storageConfig($container)['adapter'] ?? null;
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
