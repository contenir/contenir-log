<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Factory;

use Contenir\Log\Factory\LoggerFactory;
use Contenir\Log\Logger;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\ArrayContainer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

use function sys_get_temp_dir;

#[Group('unit')]
final class LoggerFactoryTest extends TestCase
{
    public function testBuildsLoggerWithTheConfiguredStorageService(): void
    {
        $container = new ArrayContainer([
            'config'     => ['log' => ['storage' => ['adapter' => 'my-storage']]],
            'my-storage' => new FilesystemStorage(sys_get_temp_dir() . '/contenir-log.log'),
        ]);

        self::assertInstanceOf(Logger::class, (new LoggerFactory())($container));
    }

    public function testFallsBackToFilesystemStorageClassWhenUnconfigured(): void
    {
        $container = new ArrayContainer([
            FilesystemStorage::class => new FilesystemStorage(sys_get_temp_dir() . '/contenir-log.log'),
        ]);

        self::assertInstanceOf(Logger::class, (new LoggerFactory())($container));
    }

    public function testThrowsWhenAdapterDoesNotResolveToStorage(): void
    {
        $container = new ArrayContainer([
            'config' => ['log' => ['storage' => ['adapter' => 'broken']]],
            'broken' => new stdClass(),
        ]);

        $this->expectException(RuntimeException::class);
        (new LoggerFactory())($container);
    }
}
