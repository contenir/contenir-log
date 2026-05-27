<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Storage\Factory;

use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\Factory\DbAdapterStorageFactory;
use Contenir\Log\Tests\TestAsset\ArrayContainer;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[Group('unit')]
final class DbAdapterStorageFactoryTest extends TestCase
{
    public function testBuildsStorageFromConfiguredAdapterTableAndColumns(): void
    {
        $container = new ArrayContainer([
            'config'     => [
                'log' => [
                    'storage' => [
                        'options' => [
                            'adapter' => 'db.adapter',
                            'table'   => 'audit',
                            'columns' => ['message' => 'msg'],
                        ],
                    ],
                ],
            ],
            'db.adapter' => $this->createMock(AdapterInterface::class),
        ]);

        self::assertInstanceOf(DbAdapterStorage::class, (new DbAdapterStorageFactory())($container));
    }

    public function testFallsBackToDefaultAdapterServiceAndTable(): void
    {
        $container = new ArrayContainer([
            'config'       => [],
            Adapter::class => $this->createMock(AdapterInterface::class),
        ]);

        self::assertInstanceOf(DbAdapterStorage::class, (new DbAdapterStorageFactory())($container));
    }

    public function testThrowsWhenAdapterServiceIsNotADbAdapter(): void
    {
        $container = new ArrayContainer([
            'config' => ['log' => ['storage' => ['options' => ['adapter' => 'broken']]]],
            'broken' => new stdClass(),
        ]);

        $this->expectException(RuntimeException::class);
        (new DbAdapterStorageFactory())($container);
    }
}
