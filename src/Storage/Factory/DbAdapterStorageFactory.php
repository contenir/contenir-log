<?php

declare(strict_types=1);

namespace Contenir\Log\Storage\Factory;

use Contenir\Log\Storage\DbAdapterStorage;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Builds {@see DbAdapterStorage} from `log.storage.options`: the `adapter`
 * service id (default {@see Adapter}), the `table` (default `log`), and the
 * `columns` and `context` maps.
 *
 * @api
 */
final class DbAdapterStorageFactory
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
     * @param array<array-key, mixed> $options
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; the value is checked here.
     */
    private static function string(array $options, string $key, string $default): string
    {
        $value = $options[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<array-key, mixed> $options
     * @param array<string, string>   $default
     *
     * @return array<string, string>
     *
     * @throws RuntimeException If the configured map is not string-to-string.
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; each entry is checked here.
     */
    private static function stringMap(array $options, string $optionName, array $default): array
    {
        $configured = $options[$optionName] ?? null;
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

    /**
     * @throws ContainerExceptionInterface
     * @throws RuntimeException When the adapter service is not a db adapter, or a map is not string-to-string.
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; the type is checked here.
     */
    public function __invoke(ContainerInterface $container): DbAdapterStorage
    {
        $options        = self::storageOptions($container);
        $adapterService = self::string($options, 'adapter', Adapter::class);
        $adapter        = $container->get($adapterService);
        if (! $adapter instanceof AdapterInterface) {
            throw new RuntimeException(sprintf(
                'contenir/contenir-log: db adapter service "%s" must implement %s.',
                $adapterService,
                AdapterInterface::class,
            ));
        }

        return new DbAdapterStorage(
            $adapter,
            self::string($options, 'table', 'log'),
            self::stringMap($options, 'columns', DbAdapterStorage::DEFAULT_COLUMNS),
            self::stringMap($options, 'context', []),
        );
    }
}
