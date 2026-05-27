<?php

declare(strict_types=1);

namespace Contenir\Log;

use DateTimeImmutable;

/**
 * Immutable representation of a single log event, handed from the Logger to a
 * StorageInterface. `error` carries the formatted exception (message chain +
 * full stack trace) when one was supplied in the PSR-3 context.
 */
final class LogRecord
{
    /**
     * @param array<array-key, mixed> $context
     */
    public function __construct(
        public readonly string $level,
        public readonly int $priority,
        public readonly string $priorityName,
        public readonly string $message,
        public readonly ?string $error,
        public readonly array $context,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }
}
