<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Integration\Storage;

use Contenir\Log\Storage\FilesystemStorage;
use Contenir\Log\Tests\TestAsset\LogRecordFactory;
use Contenir\Log\Tests\TestAsset\RacingDirectoryStreamWrapper;
use Contenir\Log\Tests\Trait\UsesTemporaryLogFileTrait;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function mkdir;

#[Group('integration')]
final class FilesystemStorageTest extends TestCase
{
    use UsesTemporaryLogFileTrait;

    #[Test]
    public function appendsToAnExistingFile(): void
    {
        mkdir(dirname($this->logFile));
        file_put_contents($this->logFile, data: "existing\n");

        (new FilesystemStorage($this->logFile))->store(LogRecordFactory::error(
            message: 'next',
            error: null,
        ));

        static::assertSame("existing\n[2026-05-27 10:00:00] ERR (3): next\n", file_get_contents($this->logFile));
    }

    #[Test]
    public function createsTheDirectoryAndWritesTheEntryFollowedByTheError(): void
    {
        (new FilesystemStorage($this->logFile))->store(LogRecordFactory::error());

        static::assertSame(
            "[2026-05-27 10:00:00] ERR (3): something broke\nRuntimeException: boom in /app.php:10\n#0 {main}\n",
            file_get_contents($this->logFile),
        );
    }

    #[Test]
    public function throwsWithoutRaisingAWarningWhenTheDirectoryCannotBeCreated(): void
    {
        mkdir(dirname($this->logFile));
        file_put_contents($this->logFile, data: '');
        $directory = "{$this->logFile}/nested";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("contenir/contenir-log: cannot create log directory \"{$directory}\".");

        (new FilesystemStorage("{$directory}/app.log"))->store(LogRecordFactory::error());
    }

    #[Test]
    public function throwsWithoutRaisingAWarningWhenTheFileCannotBeWritten(): void
    {
        mkdir($this->logFile, recursive: true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("contenir/contenir-log: cannot write to log file \"{$this->logFile}\".");

        (new FilesystemStorage($this->logFile))->store(LogRecordFactory::error());
    }

    #[Test]
    public function toleratesAnotherWorkerCreatingTheDirectoryFirst(): void
    {
        RacingDirectoryStreamWrapper::register();
        $path = RacingDirectoryStreamWrapper::SCHEME . '://logs/app.log';

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage("contenir/contenir-log: cannot write to log file \"{$path}\".");

            (new FilesystemStorage($path))->store(LogRecordFactory::error());
        } finally {
            RacingDirectoryStreamWrapper::unregister();
        }
    }

    #[Test]
    public function writesASingleLineWhenTheRecordHasNoError(): void
    {
        (new FilesystemStorage($this->logFile))->store(LogRecordFactory::error(error: null));

        static::assertSame("[2026-05-27 10:00:00] ERR (3): something broke\n", file_get_contents($this->logFile));
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
