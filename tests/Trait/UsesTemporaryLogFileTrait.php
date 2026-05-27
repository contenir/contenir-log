<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\Trait;

use function dirname;
use function is_dir;
use function is_file;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Provides a unique temporary log-file path and tidies it (and its directory)
 * away afterwards. Call setUpTemporaryLogFile()/tearDownTemporaryLogFile() from
 * the test's setUp()/tearDown() — the trait methods aren't named setUp directly
 * to avoid collisions when a class composes several traits.
 */
trait UsesTemporaryLogFileTrait
{
    private string $logFile;

    protected function setUpTemporaryLogFile(): void
    {
        $this->logFile = sys_get_temp_dir() . '/contenir-log-' . uniqid('', true) . '/app.log';
    }

    protected function tearDownTemporaryLogFile(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }

        $directory = dirname($this->logFile);
        if (is_dir($directory)) {
            rmdir($directory);
        }
    }
}
