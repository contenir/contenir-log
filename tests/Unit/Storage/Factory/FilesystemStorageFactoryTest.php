<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Storage\Factory;

use Contenir\Log\Storage\Factory\FilesystemStorageFactory;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\ArrayContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FilesystemStorageFactoryTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function unconfiguredPathProvider(): array
    {
        return [
            'no config service'      => [[]],
            'config is not an array' => [['config' => 'nonsense']],
            'log is not an array'    => [['config' => ['log' => true]]],
            'storage not an array'   => [['config' => ['log' => ['storage' => 'filesystem']]]],
            'options not an array'   => [['config' => ['log' => ['storage' => ['options' => 'x']]]]],
            'no path key'            => [['config' => ['log' => ['storage' => ['options' => []]]]]],
            'path not a string'      => [['config' => ['log' => ['storage' => ['options' => ['path' => false]]]]]],
        ];
    }

    #[Test]
    public function buildsStorageForTheConfiguredPath(): void
    {
        $container = new ArrayContainer([
            'config' => ['log' => ['storage' => ['options' => ['path' => '/var/log/site.log']]]],
        ]);

        static::assertEquals(
            new FilesystemStorage('/var/log/site.log'),
            (new FilesystemStorageFactory())($container),
        );
    }

    /**
     * @param array<string, mixed> $services
     */
    #[Test]
    #[DataProvider('unconfiguredPathProvider')]
    public function defaultsToDataLogAppLogWhenNoPathIsConfigured(array $services): void
    {
        static::assertEquals(
            new FilesystemStorage('data/log/app.log'),
            (new FilesystemStorageFactory())(new ArrayContainer($services)),
        );
    }
}
