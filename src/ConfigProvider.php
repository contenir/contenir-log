<?php

declare(strict_types=1);

namespace Contenir\Log;

use Laminas\Db\Adapter\Adapter;

/**
 * Framework-neutral config for the package.
 *
 * Mezzio consumes this directly (the `dependencies` key). The Laminas MVC
 * {@see Module} re-maps the same factories under `service_manager`. Both share
 * the `contenir_log` defaults below; override them in your application config.
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
            'contenir_log' => $this->getDefaults(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getDependencies(): array
    {
        return [
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
            'storage'    => 'filesystem',
            'filesystem' => [
                'path' => 'data/log/app.log',
            ],
            'db'         => [
                'adapter' => Adapter::class,
                'table'   => 'log',
                'columns' => Storage\DbAdapterStorage::DEFAULT_COLUMNS,
            ],
        ];
    }
}
