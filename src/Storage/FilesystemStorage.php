<?php

declare(strict_types=1);

namespace Contenir\Log\Storage;

use Contenir\Log\LogRecord;
use Override;
use RuntimeException;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const FILE_APPEND;
use const LOCK_EX;

/**
 * Appends each record to a file as a single (multi-line, when a trace is
 * present) entry. The parent directory is created on demand. Writes are
 * locked (LOCK_EX) so concurrent fpm workers don't interleave lines.
 *
 * @api
 */
final class FilesystemStorage implements StorageInterface
{
    public function __construct(
        private readonly string $path,
    ) {}

    /**
     * Append under an exclusive lock, reporting failure through the return
     * value rather than the warning file_put_contents() raises, so the caller
     * sees the RuntimeException this class documents.
     */
    private static function append(string $path, string $entry): bool
    {
        set_error_handler(static fn(): bool => true);

        try {
            return false !== file_put_contents($path, $entry, flags: FILE_APPEND | LOCK_EX);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Create a directory tree, reporting failure through the return value
     * rather than the warning mkdir() raises, so the caller can decide
     * whether a concurrent writer created it in the meantime.
     */
    private static function createDirectory(string $directory): bool
    {
        set_error_handler(static fn(): bool => true);

        try {
            return mkdir($directory, permissions: 0o775, recursive: true);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @throws RuntimeException When the directory cannot be created or the file cannot be written.
     */
    #[Override]
    public function store(LogRecord $record): void
    {
        $entry = sprintf(
            "[%s] %s (%d): %s%s\n",
            $record->createdAt->format('Y-m-d H:i:s'),
            $record->priorityName,
            $record->priority,
            $record->message,
            null === $record->error ? '' : "\n{$record->error}",
        );

        $directory = dirname($this->path);
        if (! is_dir($directory) && ! self::createDirectory($directory) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('contenir/contenir-log: cannot create log directory "%s".', $directory));
        }

        if (! self::append($this->path, $entry)) {
            throw new RuntimeException(sprintf('contenir/contenir-log: cannot write to log file "%s".', $this->path));
        }
    }
}
