<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit;

use Contenir\Log\ConfigProvider;
use Contenir\Log\Factory\LoggerFactory;
use Contenir\Log\Logger;
use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\FilesystemStorage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    public function testInvokeExposesDependenciesAndLogConfig(): void
    {
        $config = (new ConfigProvider())();

        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey('log', $config);
    }

    public function testDependenciesRegisterFactoriesAndStorageAliases(): void
    {
        $dependencies = (new ConfigProvider())->getDependencies();

        self::assertSame(LoggerFactory::class, $dependencies['factories'][Logger::class]);
        self::assertSame(DbAdapterStorage::class, $dependencies['aliases']['db']);
        self::assertSame(FilesystemStorage::class, $dependencies['aliases']['filesystem']);
    }

    public function testDefaultsSelectFilesystemStorage(): void
    {
        $defaults = (new ConfigProvider())->getDefaults();

        self::assertSame('filesystem', $defaults['storage']['adapter']);
        self::assertSame('data/log/app.log', $defaults['storage']['options']['path']);
    }
}
