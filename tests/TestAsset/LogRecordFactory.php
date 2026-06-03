<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\TestAsset;

use Contenir\Log\LogRecord;
use DateTimeImmutable;

/**
 * Builds LogRecord fixtures so tests don't inline the constructor everywhere.
 */
final class LogRecordFactory
{
    /**
     * @param array<string, mixed> $context
     */
    public static function error(
        string $message = 'something broke',
        ?string $error = "RuntimeException: boom in /app.php:10\n#0 {main}",
        array $context = [],
    ): LogRecord {
        return new LogRecord(
            level: 'error',
            priority: 3,
            priorityName: 'ERR',
            message: $message,
            error: $error,
            context: $context,
            createdAt: new DateTimeImmutable('2026-05-27 10:00:00'),
        );
    }
}
