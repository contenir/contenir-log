<?php

declare(strict_types=1);

namespace Contenir\Log\Tests\TestAsset;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class ServiceNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
