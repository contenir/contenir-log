<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Storage\Factory;

use Contenir\Log\Storage\Factory\FilesystemStorageFactory;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\ArrayContainer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FilesystemStorageFactoryTest extends TestCase
{
    public function testBuildsStorageFromConfiguredPath(): void
    {
        $container = new ArrayContainer([
            'config' => ['log' => ['storage' => ['options' => ['path' => '/tmp/contenir/app.log']]]],
        ]);

        self::assertInstanceOf(FilesystemStorage::class, (new FilesystemStorageFactory())($container));
    }

    public function testBuildsStorageWithDefaultsWhenUnconfigured(): void
    {
        self::assertInstanceOf(
            FilesystemStorage::class,
            (new FilesystemStorageFactory())(new ArrayContainer()),
        );
    }
}
