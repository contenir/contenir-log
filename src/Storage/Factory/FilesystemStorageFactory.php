<?php

declare(strict_types=1);

namespace Contenir\Log\Storage\Factory;

use Contenir\Log\Storage\FilesystemStorage;
use Psr\Container\ContainerInterface;

final class FilesystemStorageFactory
{
    public function __invoke(ContainerInterface $container): FilesystemStorage
    {
        $config  = $container->has('config') ? (array) $container->get('config') : [];
        $options = (array) ($config['log']['storage']['options'] ?? []);
        $path    = (string) ($options['path'] ?? 'data/log/app.log');

        return new FilesystemStorage($path);
    }
}
