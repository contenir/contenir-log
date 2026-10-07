<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Integration;

use Contenir\Log\Factory\LoggerFactory;
use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\Factory\DbAdapterStorageFactory;
use Contenir\Log\Storage\Factory\FilesystemStorageFactory;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\ArrayContainer;
use Contenir\Log\Tests\TestAsset\SqliteLogDatabase;
use Contenir\Log\Tests\Trait\UsesTemporaryLogFileTrait;
use Override;
use PhpDb\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function file_get_contents;

/**
 * Builds the Logger through the package's factories, as a container would,
 * and asserts what actually lands in the database or on disk.
 */
#[Group('integration')]
final class EndToEndTest extends TestCase
{
    use UsesTemporaryLogFileTrait;

    #[Test]
    #[RequiresPhpExtension('pdo_sqlite')]
    public function persistsAnErrorWithItsTraceToTheDatabase(): void
    {
        $adapter   = SqliteLogDatabase::create();
        $container = new ArrayContainer([
            'config'       => ['log' => ['storage' => ['adapter' => DbAdapterStorage::class]]],
            Adapter::class => $adapter,
        ]);
        $container = new ArrayContainer([
            'config'                => ['log' => ['storage' => ['adapter' => DbAdapterStorage::class]]],
            DbAdapterStorage::class => (new DbAdapterStorageFactory())($container),
        ]);

        $exception = new RuntimeException('boom');
        (new LoggerFactory())($container)->error('HTTP 500 at {uri}', ['uri' => '/spa', 'exception' => $exception]);

        static::assertSame(
            [[
                'message'      => 'HTTP 500 at /spa',
                'priority'     => '3',
                'priorityName' => 'ERR',
                'error'        => "RuntimeException: boom in {$exception->getFile()}:{$exception->getLine()}\n"
                    . $exception->getTraceAsString(),
            ]],
            SqliteLogDatabase::rows($adapter, columns: 'message, priority, priorityName, error'),
        );
    }

    #[Test]
    public function writesAFormattedLineToTheConfiguredFile(): void
    {
        $config    = ['log' => ['storage' => ['options' => ['path' => $this->logFile]]]];
        $container = new ArrayContainer([
            'config'                 => $config,
            FilesystemStorage::class => (new FilesystemStorageFactory())(new ArrayContainer(['config' => $config])),
        ]);

        $logger = (new LoggerFactory())($container);
        $logger->warning('disk filling up');

        static::assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] WARN \(4\): disk filling up\n$/',
            (string) file_get_contents($this->logFile),
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryLogFile();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryLogFile();
    }
}
