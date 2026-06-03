<?php

declare(strict_types=1);

namespace Contenir\Log\Storage\Factory;

use Contenir\Log\Storage\FilesystemStorage;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_string;

final class FilesystemStorageFactory
{
    public function __invoke(ContainerInterface $container): FilesystemStorage
    {
        $config     = $container->has('config') ? $container->get('config') : [];
        $config     = is_array($config) ? $config : [];
        $log        = $config['log'] ?? null;
        $log        = is_array($log) ? $log : [];
        $storageCfg = $log['storage'] ?? null;
        $storageCfg = is_array($storageCfg) ? $storageCfg : [];
        $options    = $storageCfg['options'] ?? null;
        $options    = is_array($options) ? $options : [];
        $path       = $options['path'] ?? null;
        $path       = is_string($path) ? $path : 'data/log/app.log';

        return new FilesystemStorage($path);
    }
}
