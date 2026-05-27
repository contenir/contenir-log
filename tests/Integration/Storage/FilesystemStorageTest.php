<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Integration\Storage;

use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\LogRecordFactory;
use Contenir\Log\Tests\Trait\UsesTemporaryLogFileTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function file_get_contents;
use function mkdir;
use function restore_error_handler;
use function rmdir;
use function set_error_handler;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;

#[Group('integration')]
final class FilesystemStorageTest extends TestCase
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

    public function testStoreCreatesFileWithFormattedEntryAndTrace(): void
    {
        (new FilesystemStorage($this->logFile))->store(LogRecordFactory::error());

        self::assertFileExists($this->logFile);
        $contents = (string) file_get_contents($this->logFile);
        self::assertStringContainsString('] ERR (3): something broke', $contents);
        self::assertStringContainsString('RuntimeException: boom', $contents);
    }

    public function testAppendsSuccessiveEntriesWithoutOverwriting(): void
    {
        $storage = new FilesystemStorage($this->logFile);
        $storage->store(LogRecordFactory::error(message: 'first entry'));
        $storage->store(LogRecordFactory::error(message: 'second entry'));

        $contents = (string) file_get_contents($this->logFile);
        self::assertStringContainsString('first entry', $contents);
        self::assertStringContainsString('second entry', $contents);
    }

    public function testThrowsWhenLogDirectoryCannotBeCreated(): void
    {
        // A regular file cannot host a child directory, so mkdir() fails.
        $file    = (string) tempnam(sys_get_temp_dir(), 'contenir-log');
        $storage = new FilesystemStorage($file . '/nested/app.log');

        $this->expectException(RuntimeException::class);
        try {
            set_error_handler(static fn (): bool => true);
            $storage->store(LogRecordFactory::error());
        } finally {
            restore_error_handler();
            unlink($file);
        }
    }

    public function testThrowsWhenFileCannotBeWritten(): void
    {
        // Point the storage at an existing directory: the parent exists (so
        // mkdir is skipped) but writing the "file" fails.
        $directory = sys_get_temp_dir() . '/contenir-log-' . uniqid('', true);
        mkdir($directory, 0o775, true);
        $storage = new FilesystemStorage($directory);

        $this->expectException(RuntimeException::class);
        try {
            set_error_handler(static fn (): bool => true);
            $storage->store(LogRecordFactory::error());
        } finally {
            restore_error_handler();
            rmdir($directory);
        }
    }
}
