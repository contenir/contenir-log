<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\TestAsset;

use Contenir\Log\LogRecord;
use Contenir\Log\Storage\StorageInterface;

/**
 * In-memory storage that captures records for assertions.
 */
final class CapturingStorage implements StorageInterface
{
    /** @var list<LogRecord> */
    public array $records = [];

    public function store(LogRecord $record): void
    {
        $this->records[] = $record;
    }
}
