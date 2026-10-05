<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit;

use Contenir\Log\ConfigProvider;
use Contenir\Log\Module;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ModuleTest extends TestCase
{
    #[Test]
    public function exposesProviderServicesUnderServiceManagerKey(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(
            [
                'service_manager' => $provider->getDependencies(),
                'log'             => $provider->getDefaults(),
            ],
            (new Module())->getConfig(),
        );
    }
}
