<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Integration;

use Contenir\Log\Logger;
use Contenir\Log\Storage\DbAdapterStorage;
use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\SqliteLogDatabase;
use Contenir\Log\Tests\Trait\UsesTemporaryLogFileTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function extension_loaded;
use function file_get_contents;

/**
 * Drives the Logger end to end through the real storage backends, asserting
 * what actually lands in the database / on disk.
 */
#[Group('integration')]
final class EndToEndTest extends TestCase
{
    use UsesTemporaryLogFileTrait;

    protected function setUp(): void
    {
        $this->setUpTemporaryLogFile();
    }

    protected function tearDown(): void
    {
        $this->tearDownTemporaryLogFile();
    }

    public function testLoggerPersistsErrorToDatabaseWithFullTrace(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is not available');
        }

        $adapter = SqliteLogDatabase::create();
        $logger  = new Logger(new DbAdapterStorage($adapter));

        $logger->error('HTTP 500 at /spa', ['exception' => new RuntimeException('boom')]);

        $rows = SqliteLogDatabase::rows($adapter);
        self::assertCount(1, $rows);
        self::assertSame('HTTP 500 at /spa', $rows[0]['message']);
        self::assertSame('ERR', $rows[0]['priorityName']);
        self::assertSame('3', (string) $rows[0]['priority']);
        self::assertStringContainsString('RuntimeException: boom', (string) $rows[0]['error']);
        self::assertStringContainsString('#0', (string) $rows[0]['error']);
    }

    public function testLoggerWritesFormattedLineToFile(): void
    {
        $logger = new Logger(new FilesystemStorage($this->logFile));

        $logger->warning('disk filling up');

        $contents = (string) file_get_contents($this->logFile);
        self::assertStringContainsString('] WARN (4): disk filling up', $contents);
    }
}
