<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Storage\Factory;

use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\Factory\DbAdapterStorageFactory;
use Contenir\Log\Tests\TestAsset\ArrayContainer;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[Group('unit')]
final class DbAdapterStorageFactoryTest extends TestCase
{
    private AdapterInterface $adapter;

    /**
     * @return array<string, array{string, array<array-key, mixed>}>
     */
    public static function invalidMapProvider(): array
    {
        return [
            'columns with a non-string column' => ['columns', ['message' => ['nested']]],
            'columns with an integer key'      => ['columns', ['msg']],
            'context with a non-string column' => ['context', ['student' => 7]],
            'context with an integer key'      => ['context', ['student_id']],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unconfiguredOptionsProvider(): array
    {
        return [
            'no config service'      => [null],
            'config is not an array' => ['nonsense'],
            'log is not an array'    => [['log' => true]],
            'storage not an array'   => [['log' => ['storage' => 'db']]],
            'options not an array'   => [['log' => ['storage' => ['options' => 'x']]]],
            'empty options'          => [['log' => ['storage' => ['options' => []]]]],
            'options of wrong types' => [[
                'log' => [
                    'storage' => ['options' => [
                        'adapter' => 1,
                        'table'   => 2,
                        'columns' => 'message',
                        'context' => 'student',
                    ]],
                ],
            ]],
        ];
    }

    #[Test]
    public function buildsStorageFromTheConfiguredAdapterTableAndMaps(): void
    {
        $container = new ArrayContainer([
            'config'     => [
                'log' => [
                    'storage' => [
                        'options' => [
                            'adapter' => 'db.adapter',
                            'table'   => 'audit',
                            'columns' => ['message' => 'msg'],
                            'context' => ['student' => 'student_id'],
                        ],
                    ],
                ],
            ],
            'db.adapter' => $this->adapter,
        ]);

        static::assertEquals(
            new DbAdapterStorage($this->adapter, 'audit', ['message' => 'msg'], ['student' => 'student_id']),
            (new DbAdapterStorageFactory())($container),
        );
    }

    #[Test]
    #[DataProvider('unconfiguredOptionsProvider')]
    public function fallsBackToTheDefaultAdapterServiceTableAndColumns(mixed $config): void
    {
        $services = [Adapter::class => $this->adapter];
        if (null !== $config) {
            $services['config'] = $config;
        }

        static::assertEquals(
            new DbAdapterStorage($this->adapter, 'log', DbAdapterStorage::DEFAULT_COLUMNS, []),
            (new DbAdapterStorageFactory())(new ArrayContainer($services)),
        );
    }

    /**
     * @param array<array-key, mixed> $map
     */
    #[Test]
    #[DataProvider('invalidMapProvider')]
    public function rejectsAMapThatIsNotStringToString(string $option, array $map): void
    {
        $container = new ArrayContainer([
            'config'       => ['log' => ['storage' => ['options' => [$option => $map]]]],
            Adapter::class => $this->adapter,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "contenir/contenir-log: log storage \"{$option}\" must map string keys to string column names.",
        );

        (new DbAdapterStorageFactory())($container);
    }

    #[Test]
    public function rejectsAnAdapterServiceThatIsNotADbAdapter(): void
    {
        $container = new ArrayContainer([
            'config' => ['log' => ['storage' => ['options' => ['adapter' => 'broken']]]],
            'broken' => new stdClass(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'contenir/contenir-log: db adapter service "broken" must implement Laminas\Db\Adapter\AdapterInterface.',
        );

        (new DbAdapterStorageFactory())($container);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->adapter = $this->createStub(AdapterInterface::class);
    }
}
