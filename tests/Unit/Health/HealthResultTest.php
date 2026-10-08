<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Health;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Health\CheckResult;
use V3Cache\OpcacheManager\Health\HealthResult;
use V3Cache\OpcacheManager\Health\HealthStatus;

final class HealthResultTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $checks = [
            'hitRate' => new CheckResult('hitRate', HealthStatus::OK, 95.0, 80.0, 'OK'),
        ];

        $result = new HealthResult(
            status: HealthStatus::OK,
            checks: $checks,
            summary: 'All checks passed',
        );

        $this->assertSame(HealthStatus::OK, $result->status);
        $this->assertSame($checks, $result->checks);
        $this->assertSame('All checks passed', $result->summary);
    }

    public function testFromArrayCreatesInstance(): void
    {
        $data = [
            'status' => 'ok',
            'checks' => [
                'hitRate' => [
                    'name' => 'hitRate',
                    'status' => 'ok',
                    'actual_value' => 95.0,
                    'threshold' => 80.0,
                    'message' => 'OK',
                ],
            ],
            'summary' => 'All checks passed',
        ];

        $result = HealthResult::fromArray($data);

        $this->assertSame(HealthStatus::OK, $result->status);
        $this->assertCount(1, $result->checks);
        $this->assertSame('All checks passed', $result->summary);
    }

    public function testFromArrayThrowsOnMissingKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        HealthResult::fromArray(['status' => 'ok']);
    }
}
