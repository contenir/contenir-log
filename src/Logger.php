<?php

declare(strict_types=1);

namespace Contenir\Log;

use Contenir\Log\Storage\StorageInterface;
use DateTimeImmutable;
use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

use function implode;
use function is_scalar;
use function sprintf;
use function strtr;

/**
 * PSR-3 logger that builds a LogRecord per event and hands it to the
 * configured StorageInterface. A Throwable supplied in the context under the
 * `exception` key is formatted (message chain + full trace) into the record's
 * `error` field; all other scalar context values interpolate PSR-3
 * {placeholders} in the message.
 *
 * priority/priorityName follow Laminas\Log's numeric scheme so existing log
 * tables built for it remain compatible.
 */
final class Logger extends AbstractLogger
{
    /** @var array<string, array{int, string}> PSR-3 level => [priority, priorityName]. */
    private const PRIORITIES = [
        'emergency' => [0, 'EMERG'],
        'alert'     => [1, 'ALERT'],
        'critical'  => [2, 'CRIT'],
        'error'     => [3, 'ERR'],
        'warning'   => [4, 'WARN'],
        'notice'    => [5, 'NOTICE'],
        'info'      => [6, 'INFO'],
        'debug'     => [7, 'DEBUG'],
    ];

    public function __construct(private readonly StorageInterface $storage)
    {
    }

    /**
     * @param mixed                   $level
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $level = is_scalar($level) || $level instanceof Stringable ? (string) $level : '';

        [$priority, $priorityName] = self::PRIORITIES[$level] ?? [7, $level];

        $exception = $context['exception'] ?? null;

        $this->storage->store(new LogRecord(
            level: $level,
            priority: $priority,
            priorityName: $priorityName,
            message: $this->interpolate((string) $message, $context),
            error: $exception instanceof Throwable ? $this->formatException($exception) : null,
            context: $context,
            createdAt: new DateTimeImmutable(),
        ));
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            if ($key === 'exception') {
                continue;
            }
            if (is_scalar($value) || $value instanceof Stringable) {
                $replacements['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replacements);
    }

    private function formatException(Throwable $exception): string
    {
        $lines     = [];
        $throwable = $exception;
        while ($throwable !== null) {
            $lines[]   = sprintf(
                '%s: %s in %s:%d',
                $throwable::class,
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine()
            );
            $throwable = $throwable->getPrevious();
        }

        return implode("\nCaused by ", $lines) . "\n" . $exception->getTraceAsString();
    }
}
