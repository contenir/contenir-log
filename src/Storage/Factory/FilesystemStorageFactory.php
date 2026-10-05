<?php

declare(strict_types=1);

namespace Contenir\Log\Storage\Factory;

use Contenir\Log\Storage\FilesystemStorage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_string;

/**
 * Builds {@see FilesystemStorage} from `log.storage.options.path`, defaulting
 * to `data/log/app.log`.
 *
 * @api
 */
final class FilesystemStorageFactory
{
    /**
     * @return array<array-key, mixed>
     *
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; each level is checked here.
     */
    private static function storageOptions(ContainerInterface $container): array
    {
        $value = $container->has('config') ? $container->get('config') : [];
        foreach (['log', 'storage', 'options'] as $key) {
            $value = is_array($value) ? $value[$key] ?? null : null;
        }

        return is_array($value) ? $value : [];
    }

    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; the path is checked here.
     */
    public function __invoke(ContainerInterface $container): FilesystemStorage
    {
        $path = self::storageOptions($container)['path'] ?? null;

        return new FilesystemStorage(is_string($path) ? $path : 'data/log/app.log');
    }
}
