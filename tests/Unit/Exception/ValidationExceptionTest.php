<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Exception\ValidationException;

final class ValidationExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new ValidationException('test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testGetErrorsReturnsEmptyArrayByDefault(): void
    {
        $exception = new ValidationException('test');

        $this->assertSame([], $exception->getErrors());
    }

    public function testGetErrorsReturnsProvidedErrors(): void
    {
        $errors = ['field' => 'error message'];
        $exception = new ValidationException('test', $errors);

        $this->assertSame($errors, $exception->getErrors());
    }

    public function testMessageIsPreserved(): void
    {
        $exception = new ValidationException('custom message');

        $this->assertSame('custom message', $exception->getMessage());
    }
}
