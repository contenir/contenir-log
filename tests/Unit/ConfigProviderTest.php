<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit;

use Contenir\Log\ConfigProvider;
use Contenir\Log\Factory\LoggerFactory;
use Contenir\Log\Logger;
use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\Factory\DbAdapterStorageFactory;
use Contenir\Log\Storage\Factory\FilesystemStorageFactory;
use Contenir\Log\Storage\FilesystemStorage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function defaultsSelectFilesystemStorageUnderDataLog(): void
    {
        static::assertSame(
            ['storage' => ['adapter' => 'filesystem', 'options' => ['path' => 'data/log/app.log']]],
            (new ConfigProvider())->getDefaults(),
        );
    }

    #[Test]
    public function dependenciesRegisterFactoriesAndStorageAliases(): void
    {
        static::assertSame(
            [
                'aliases'   => [
                    'db'         => DbAdapterStorage::class,
                    'filesystem' => FilesystemStorage::class,
                ],
                'factories' => [
                    Logger::class            => LoggerFactory::class,
                    FilesystemStorage::class => FilesystemStorageFactory::class,
                    DbAdapterStorage::class  => DbAdapterStorageFactory::class,
                ],
            ],
            (new ConfigProvider())->getDependencies(),
        );
    }

    #[Test]
    public function invokingExposesDependenciesAndLogDefaults(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(
            [
                'dependencies' => $provider->getDependencies(),
                'log'          => $provider->getDefaults(),
            ],
            $provider(),
        );
    }
}
