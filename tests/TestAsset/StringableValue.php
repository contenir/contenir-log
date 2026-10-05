<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\TestAsset;

use Override;
use Stringable;

/**
 * A Stringable that renders a fixed string, for levels, messages and context
 * values passed as objects.
 */
final readonly class StringableValue implements Stringable
{
    public function __construct(
        private string $value,
    ) {}

    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
