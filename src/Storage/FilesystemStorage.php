<?php

declare(strict_types=1);

namespace Contenir\Log\Storage;

use Contenir\Log\LogRecord;
use RuntimeException;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sprintf;

use const FILE_APPEND;
use const LOCK_EX;

/**
 * Appends each record to a file as a single (multi-line, when a trace is
 * present) entry. The parent directory is created on demand. Writes are
 * locked (LOCK_EX) so concurrent fpm workers don't interleave lines.
 */
final class FilesystemStorage implements StorageInterface
{
    public function __construct(private readonly string $path)
    {
    }

    public function store(LogRecord $record): void
    {
        $entry = sprintf(
            "[%s] %s (%d): %s%s\n",
            $record->createdAt->format('Y-m-d H:i:s'),
            $record->priorityName,
            $record->priority,
            $record->message,
            $record->error !== null ? "\n" . $record->error : '',
        );

        $directory = dirname($this->path);
        if (! is_dir($directory) && ! mkdir($directory, 0o775, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('contenir/contenir-log: cannot create log directory "%s".', $directory));
        }

        if (file_put_contents($this->path, $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException(sprintf('contenir/contenir-log: cannot write to log file "%s".', $this->path));
        }
    }
}
