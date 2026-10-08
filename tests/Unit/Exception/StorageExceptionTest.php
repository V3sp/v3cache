<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Exception\StorageException;

final class StorageExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new StorageException('test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testMessageIsPreserved(): void
    {
        $exception = new StorageException('storage error');

        $this->assertSame('storage error', $exception->getMessage());
    }
}
