<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit;

use Contenir\Log\ConfigProvider;
use Contenir\Log\Module;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ModuleTest extends TestCase
{
    public function testGetConfigMapsDependenciesOntoServiceManager(): void
    {
        $config = (new Module())->getConfig();

        self::assertSame((new ConfigProvider())->getDependencies(), $config['service_manager']);
        self::assertArrayHasKey('log', $config);
    }
}
