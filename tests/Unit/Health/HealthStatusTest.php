<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Health;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Health\HealthStatus;

final class HealthStatusTest extends TestCase
{
    public function testOkValue(): void
    {
        $this->assertSame('ok', HealthStatus::OK->value);
    }

    public function testWarnValue(): void
    {
        $this->assertSame('warn', HealthStatus::WARN->value);
    }

    public function testCritValue(): void
    {
        $this->assertSame('crit', HealthStatus::CRIT->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(HealthStatus::OK, HealthStatus::from('ok'));
        $this->assertSame(HealthStatus::WARN, HealthStatus::from('warn'));
        $this->assertSame(HealthStatus::CRIT, HealthStatus::from('crit'));
    }

    public function testTryFromReturnsNullForInvalid(): void
    {
        $this->assertNull(HealthStatus::tryFrom('invalid'));
    }
}
