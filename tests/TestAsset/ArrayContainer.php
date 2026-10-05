<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\TestAsset;

use Override;
use Psr\Container\ContainerInterface;

use function array_key_exists;

/**
 * Minimal PSR-11 container backed by an array, for exercising factories with a
 * known `config` service and pre-built collaborators.
 *
 * The `$id` parameters are natively untyped so the same class satisfies both
 * psr/container 1.0 (untyped) and 2.0 (`string`), the range this package allows.
 */
final class ArrayContainer implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services
     */
    public function __construct(
        private array $services = [],
    ) {}

    /**
     * @param string $id
     */
    #[Override]
    public function get($id): mixed
    {
        if (! $this->has($id)) {
            throw new ServiceNotFoundException("Service \"{$id}\" is not registered.");
        }

        return $this->services[$id];
    }

    /**
     * @param string $id
     */
    #[Override]
    public function has($id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
