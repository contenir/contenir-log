<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit;

use Contenir\Log\Logger;
use Contenir\Log\LogRecord;
use Contenir\Log\Tests\TestAsset\CapturingStorage;
use Contenir\Log\Tests\TestAsset\StringableValue;
use DateTimeImmutable;
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use stdClass;

use function sprintf;

#[Group('unit')]
final class LoggerTest extends TestCase
{
    private CapturingStorage $storage;

    private Logger $logger;

    /**
     * @return array<string, array{string, array<array-key, mixed>, string}>
     */
    public static function interpolationProvider(): array
    {
        return [
            'string value'           => ['at {uri}', ['uri' => '/spa'], 'at /spa'],
            'integer value'          => ['id {id}', ['id' => 42], 'id 42'],
            'float value'            => ['took {secs}s', ['secs' => 1.5], 'took 1.5s'],
            'boolean value'          => ['flag {on}', ['on' => true], 'flag 1'],
            'stringable value'       => ['user {user}', ['user' => new StringableValue('ann')], 'user ann'],
            'integer key'            => ['first {0}', ['zero'], 'first zero'],
            'array value is skipped' => ['ids {ids}', ['ids' => [1, 2]], 'ids {ids}'],
            'object value skipped'   => ['obj {obj}', ['obj' => new stdClass()], 'obj {obj}'],
            'null value is skipped'  => ['val {val}', ['val' => null], 'val {val}'],
            'unknown placeholder'    => ['at {uri}', [], 'at {uri}'],
            'exception not inlined'  => ['{exception}', ['exception' => 'text'], '{exception}'],
        ];
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function levelProvider(): array
    {
        return [
            'emergency' => [LogLevel::EMERGENCY, 0, 'EMERG'],
            'alert'     => [LogLevel::ALERT, 1, 'ALERT'],
            'critical'  => [LogLevel::CRITICAL, 2, 'CRIT'],
            'error'     => [LogLevel::ERROR, 3, 'ERR'],
            'warning'   => [LogLevel::WARNING, 4, 'WARN'],
            'notice'    => [LogLevel::NOTICE, 5, 'NOTICE'],
            'info'      => [LogLevel::INFO, 6, 'INFO'],
            'debug'     => [LogLevel::DEBUG, 7, 'DEBUG'],
        ];
    }

    /**
     * @return array<string, array{mixed, string, string}>
     */
    public static function unrecognisedLevelProvider(): array
    {
        return [
            'custom string level' => ['audit', 'audit', 'audit'],
            'integer level'       => [42, '42', '42'],
            'stringable level'    => [new StringableValue('trace'), 'trace', 'trace'],
            'non-scalar level'    => [['error'], '', ''],
        ];
    }

    #[Test]
    public function acceptsAStringableMessage(): void
    {
        $this->logger->info(new StringableValue('hello {name}'), ['name' => 'world']);

        static::assertSame('hello world', $this->onlyRecord()->message);
    }

    #[Test]
    public function formatsTheExceptionChainFollowedByTheOutermostTrace(): void
    {
        $inner = new LogicException('inner');
        $outer = new RuntimeException('outer', 0, $inner);

        $this->logger->error('failed', ['exception' => $outer]);

        static::assertSame(
            sprintf(
                "RuntimeException: outer in %s:%d\nCaused by LogicException: inner in %s:%d\n%s",
                $outer->getFile(),
                $outer->getLine(),
                $inner->getFile(),
                $inner->getLine(),
                $outer->getTraceAsString(),
            ),
            $this->onlyRecord()->error,
        );
    }

    #[Test]
    public function ignoresAnExceptionEntryThatIsNotThrowable(): void
    {
        $this->logger->error('failed', ['exception' => 'not a throwable']);

        static::assertNull($this->onlyRecord()->error);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    #[Test]
    #[DataProvider('interpolationProvider')]
    public function interpolatesScalarAndStringableContextIntoPlaceholders(
        string $message,
        array $context,
        string $expected,
    ): void {
        $this->logger->info($message, $context);

        static::assertSame($expected, $this->onlyRecord()->message);
    }

    #[Test]
    public function keepsTheFullContextOnTheRecord(): void
    {
        $context = ['uri' => '/spa', 'exception' => new RuntimeException('boom'), 'ids' => [1, 2]];

        $this->logger->error('failed', $context);

        static::assertSame($context, $this->onlyRecord()->context);
    }

    #[Test]
    public function levelShortcutsDelegateToLog(): void
    {
        $this->logger->warning('disk filling up');

        static::assertSame('WARN', $this->onlyRecord()->priorityName);
    }

    #[Test]
    #[DataProvider('levelProvider')]
    public function mapsPsrLevelsToLaminasPriorities(string $level, int $priority, string $priorityName): void
    {
        $this->logger->log($level, 'message');

        $record = $this->onlyRecord();
        static::assertSame(
            [$level, $priority, $priorityName],
            [$record->level, $record->priority, $record->priorityName],
        );
    }

    #[Test]
    public function recordsNoErrorWithoutAnException(): void
    {
        $this->logger->info('just so you know');

        static::assertNull($this->onlyRecord()->error);
    }

    #[Test]
    #[DataProvider('unrecognisedLevelProvider')]
    public function recordsUnrecognisedLevelsAtDebugPriorityUnderTheirOwnName(
        mixed $level,
        string $expectedLevel,
        string $expectedName,
    ): void {
        $this->logger->log($level, 'message');

        $record = $this->onlyRecord();
        static::assertSame(
            [$expectedLevel, 7, $expectedName],
            [$record->level, $record->priority, $record->priorityName],
        );
    }

    #[Test]
    public function stampsTheRecordWithTheCurrentTime(): void
    {
        $before = new DateTimeImmutable();
        $this->logger->info('now');
        $after = new DateTimeImmutable();

        $createdAt = $this->onlyRecord()->createdAt;
        static::assertTrue($before <= $createdAt && $createdAt <= $after);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->storage = new CapturingStorage();
        $this->logger  = new Logger($this->storage);
    }

    private function onlyRecord(): LogRecord
    {
        static::assertCount(1, $this->storage->records);

        return $this->storage->records[0];
    }
}
