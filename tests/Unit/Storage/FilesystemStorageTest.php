<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit\Storage;

use Contenir\Log\LogRecord;
use Contenir\Log\Storage\FilesystemStorage;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function is_dir;
use function is_file;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[Group('unit')]
final class FilesystemStorageTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/contenir-log-' . uniqid() . '/app.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        $dir = dirname($this->path);
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public function testStoreCreatesFileAndAppendsFormattedEntryWithTrace(): void
    {
        $storage = new FilesystemStorage($this->path);

        $storage->store(new LogRecord(
            level: 'error',
            priority: 3,
            priorityName: 'ERR',
            message: 'something broke',
            error: "RuntimeException: boom in /app.php:10\n#0 {main}",
            context: [],
            createdAt: new DateTimeImmutable('2026-05-27 10:00:00'),
        ));

        self::assertFileExists($this->path);
        $contents = (string) file_get_contents($this->path);
        self::assertStringContainsString('[2026-05-27 10:00:00] ERR (3): something broke', $contents);
        self::assertStringContainsString('RuntimeException: boom', $contents);
    }
}
