<?php

declare(strict_types=1);

namespace Contenir\Log\Storage;

use Contenir\Log\LogRecord;
use RuntimeException;

/**
 * A destination a Logger persists records to. Implementations must not throw
 * for routine write failures in a way that would mask the original error the
 * caller was trying to log — see each implementation's contract.
 *
 * @api
 */
interface StorageInterface
{
    /**
     * @throws RuntimeException When the record cannot be persisted.
     */
    public function store(LogRecord $record): void;
}
