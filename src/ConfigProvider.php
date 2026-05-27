<?php

declare(strict_types=1);

namespace Contenir\Log;

/**
 * Framework-neutral config for the package.
 *
 * Mezzio consumes this directly (the `dependencies` key). The Laminas MVC
 * {@see Module} re-maps the same factories under `service_manager`. Both share
 * the `log` defaults below; override them in your application config.
 *
 * `log.storage.adapter` is resolved straight through the container, so the
 * built-in `db` / `filesystem` aliases (below) or any service id implementing
 * StorageInterface may be named.
 */
final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'log'          => $this->getDefaults(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                'db'         => Storage\DbAdapterStorage::class,
                'filesystem' => Storage\FilesystemStorage::class,
            ],
            'factories' => [
                Logger::class                    => Factory\LoggerFactory::class,
                Storage\FilesystemStorage::class => Storage\Factory\FilesystemStorageFactory::class,
                Storage\DbAdapterStorage::class  => Storage\Factory\DbAdapterStorageFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        return [
            'storage' => [
                'adapter' => 'filesystem',
                'options' => [
                    'path' => 'data/log/app.log',
                ],
            ],
        ];
    }
}
