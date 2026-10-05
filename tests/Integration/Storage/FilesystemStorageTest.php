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

use function clearstatcache;
use function dirname;
use function error_clear_last;
use function error_get_last;
use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function mkdir;
use function umask;

#[Group('integration')]
final class FilesystemStorageTest extends TestCase
{
    use UsesTemporaryLogFileTrait;

    /**
     * Store a record that is expected to fail, returning the exception message
     * and whatever error PHP recorded meanwhile, so a suppressed warning is
     * proven not to have reached PHP's own error handling.
     *
     * @return array{string, array{type: int, message: string, file: string, line: int}|null}
     */
    private static function failureAndLastError(FilesystemStorage $storage): array
    {
        error_clear_last();

        try {
            $storage->store(LogRecordFactory::error());
        } catch (RuntimeException $exception) {
            return [$exception->getMessage(), error_get_last()];
        }

        static::fail('Expected the storage to throw a RuntimeException.');
    }

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
    public function createsTheDirectoryGroupWritableSubjectToTheUmask(): void
    {
        $previousUmask = umask(0);

        try {
            (new FilesystemStorage($this->logFile))->store(LogRecordFactory::error());
        } finally {
            umask($previousUmask);
        }

        clearstatcache();
        static::assertSame(0o775, fileperms(dirname($this->logFile)) & 0o777);
    }

    #[Test]
    public function throwsWithoutRaisingAWarningWhenTheDirectoryCannotBeCreated(): void
    {
        mkdir(dirname($this->logFile));
        file_put_contents($this->logFile, data: '');
        $directory = "{$this->logFile}/nested";

        static::assertSame(
            [
                "contenir/contenir-log: cannot create log directory \"{$directory}\".",
                null,
            ],
            self::failureAndLastError(new FilesystemStorage("{$directory}/app.log")),
        );
    }

    #[Test]
    public function throwsWithoutRaisingAWarningWhenTheFileCannotBeWritten(): void
    {
        mkdir($this->logFile, recursive: true);

        static::assertSame(
            ["contenir/contenir-log: cannot write to log file \"{$this->logFile}\".", null],
            self::failureAndLastError(new FilesystemStorage($this->logFile)),
        );
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
