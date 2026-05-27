<?php

declare(strict_types=1);

namespace Contenir\Log\Storage;

use Contenir\Log\LogRecord;

/**
 * A destination a Logger persists records to. Implementations must not throw
 * for routine write failures in a way that would mask the original error the
 * caller was trying to log — see each implementation's contract.
 */
interface StorageInterface
{
    public function store(LogRecord $record): void;
}
