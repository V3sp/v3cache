<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Health;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Health\CheckResult;
use V3Cache\OpcacheManager\Health\HealthStatus;

final class CheckResultTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $result = new CheckResult(
            name: 'hitRate',
            status: HealthStatus::WARN,
            actualValue: 62.3,
            threshold: 80.0,
            message: 'Hit rate 62.3% below threshold 80.0%',
        );

        $this->assertSame('hitRate', $result->name);
        $this->assertSame(HealthStatus::WARN, $result->status);
        $this->assertSame(62.3, $result->actualValue);
        $this->assertSame(80.0, $result->threshold);
        $this->assertSame('Hit rate 62.3% below threshold 80.0%', $result->message);
    }

    public function testFromArrayCreatesInstance(): void
    {
        $data = [
            'name' => 'hitRate',
            'status' => 'warn',
            'actual_value' => 62.3,
            'threshold' => 80.0,
            'message' => 'Hit rate 62.3% below threshold 80.0%',
        ];

        $result = CheckResult::fromArray($data);

        $this->assertSame('hitRate', $result->name);
        $this->assertSame(HealthStatus::WARN, $result->status);
        $this->assertSame(62.3, $result->actualValue);
        $this->assertSame(80.0, $result->threshold);
        $this->assertSame('Hit rate 62.3% below threshold 80.0%', $result->message);
    }

    public function testFromArrayThrowsOnMissingKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CheckResult::fromArray(['name' => 'hitRate']);
    }
}
