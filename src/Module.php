<?php

declare(strict_types=1);

namespace Contenir\Log;

/**
 * Laminas MVC module shim. Maps {@see ConfigProvider}'s framework-neutral
 * `dependencies` onto the `service_manager` key the MVC container reads, so the
 * same package serves both MVC (this Module) and Mezzio (the ConfigProvider).
 */
final class Module
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return [
            'service_manager' => $provider->getDependencies(),
            'log'             => $provider->getDefaults(),
        ];
    }
}
