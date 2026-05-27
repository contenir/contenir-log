<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Unit;

use Contenir\Log\Logger;
use Contenir\Log\Tests\TestAsset\CapturingStorage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[Group('unit')]
final class LoggerTest extends TestCase
{
    public function testErrorMapsPriorityInterpolatesMessageAndFormatsException(): void
    {
        $storage = new CapturingStorage();
        $logger  = new Logger($storage);

        $logger->error('HTTP 500 at {uri}', [
            'uri'       => '/spa',
            'exception' => new RuntimeException('boom'),
        ]);

        self::assertCount(1, $storage->records);
        $record = $storage->records[0];

        self::assertSame(3, $record->priority);
        self::assertSame('ERR', $record->priorityName);
        self::assertSame('HTTP 500 at /spa', $record->message);
        self::assertNotNull($record->error);
        self::assertStringContainsString('RuntimeException: boom', $record->error);
        self::assertStringContainsString('#0', $record->error);
    }

    public function testInfoHasNoErrorAndMapsPriority(): void
    {
        $storage = new CapturingStorage();

        (new Logger($storage))->info('just so you know');

        $record = $storage->records[0];
        self::assertSame(6, $record->priority);
        self::assertSame('INFO', $record->priorityName);
        self::assertNull($record->error);
        self::assertSame('just so you know', $record->message);
    }
}
