<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Exception\OpcacheNotEnabledException;

final class OpcacheNotEnabledExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new OpcacheNotEnabledException('test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testMessageIsPreserved(): void
    {
        $exception = new OpcacheNotEnabledException('opcache not enabled');

        $this->assertSame('opcache not enabled', $exception->getMessage());
    }
}
