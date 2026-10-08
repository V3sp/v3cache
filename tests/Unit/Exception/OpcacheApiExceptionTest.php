<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Exception\OpcacheApiException;

final class OpcacheApiExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new OpcacheApiException('test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testMessageIsPreserved(): void
    {
        $exception = new OpcacheApiException('api error');

        $this->assertSame('api error', $exception->getMessage());
    }
}
