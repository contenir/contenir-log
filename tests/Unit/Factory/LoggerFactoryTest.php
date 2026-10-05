<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Factory;

use Contenir\Log\Factory\LoggerFactory;
use Contenir\Log\Logger;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\ArrayContainer;
use Contenir\Log\Tests\TestAsset\CapturingStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[Group('unit')]
final class LoggerFactoryTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function unconfiguredAdapterProvider(): array
    {
        return [
            'no config service'      => [[]],
            'config is not an array' => [['config' => 'nonsense']],
            'no log key'             => [['config' => []]],
            'log is not an array'    => [['config' => ['log' => true]]],
            'storage not an array'   => [['config' => ['log' => ['storage' => 'db']]]],
            'no adapter key'         => [['config' => ['log' => ['storage' => []]]]],
            'adapter not a string'   => [['config' => ['log' => ['storage' => ['adapter' => 42]]]]],
        ];
    }

    #[Test]
    public function buildsLoggerAroundTheConfiguredStorageService(): void
    {
        $storage   = new CapturingStorage();
        $container = new ArrayContainer([
            'config'     => ['log' => ['storage' => ['adapter' => 'my-storage']]],
            'my-storage' => $storage,
        ]);

        static::assertEquals(new Logger($storage), (new LoggerFactory())($container));
    }

    /**
     * @param array<string, mixed> $services
     */
    #[Test]
    #[DataProvider('unconfiguredAdapterProvider')]
    public function fallsBackToFilesystemStorageServiceWhenNoAdapterIsConfigured(array $services): void
    {
        $storage   = new FilesystemStorage('/var/log/app.log');
        $container = new ArrayContainer([...$services, FilesystemStorage::class => $storage]);

        static::assertEquals(new Logger($storage), (new LoggerFactory())($container));
    }

    #[Test]
    public function rejectsAnAdapterServiceThatIsNotAStorage(): void
    {
        $container = new ArrayContainer([
            'config' => ['log' => ['storage' => ['adapter' => 'broken']]],
            'broken' => new stdClass(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'contenir/contenir-log: storage adapter "broken" must resolve to a Contenir\Log\Storage\StorageInterface.',
        );

        (new LoggerFactory())($container);
    }
}
