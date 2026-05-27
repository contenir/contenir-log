<?php

declare(strict_types=1);

namespace Contenir\Log\Factory;

use Contenir\Log\Logger;
use Contenir\Log\Storage\StorageInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function sprintf;

final class LoggerFactory
{
    public function __invoke(ContainerInterface $container): Logger
    {
        $config  = $container->has('config') ? (array) $container->get('config') : [];
        $adapter = (string) ($config['log']['storage']['adapter'] ?? 'filesystem');

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
