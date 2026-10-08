<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Health;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Health\HealthChecker;
use V3Cache\OpcacheManager\Health\HealthResult;
use V3Cache\OpcacheManager\Health\HealthStatus;
use V3Cache\OpcacheManager\Health\HealthThresholds;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;

final class HealthCheckerTest extends TestCase
{
    public function testCheckReturnsOkForHealthyMetrics(): void
    {
        $checker = new HealthChecker(new HealthThresholds());
        $metrics = $this->createMetrics(
            hitRate: 95.0,
            memoryWasted: 0.0,
            restarts: 0,
            memoryUsage: 50.0,
        );

        $result = $checker->check($metrics);

        $this->assertSame(HealthStatus::OK, $result->status);
    }

    public function testCheckReturnsWarnForLowHitRate(): void
    {
        $checker = new HealthChecker(new HealthThresholds(minHitRate: 80.0));
        $metrics = $this->createMetrics(hitRate: 62.3);

        $result = $checker->check($metrics);

        $this->assertSame(HealthStatus::WARN, $result->status);
    }

    public function testCheckReturnsCritForRestarts(): void
    {
        $checker = new HealthChecker(new HealthThresholds(maxRestarts: 0));
        $metrics = $this->createMetrics(restarts: 1);

        $result = $checker->check($metrics);

        $this->assertSame(HealthStatus::CRIT, $result->status);
    }

    public function testCheckReturnsWarnForHighWastedMemory(): void
    {
        $checker = new HealthChecker(new HealthThresholds(maxWastedPercent: 20.0));
        $metrics = $this->createMetrics(memoryWasted: 25.0);

        $result = $checker->check($metrics);

        $this->assertSame(HealthStatus::WARN, $result->status);
    }

    public function testCheckReturnsWarnForHighMemoryUsage(): void
    {
        $checker = new HealthChecker(new HealthThresholds(maxMemoryUsagePercent: 90.0));
        $metrics = $this->createMetrics(memoryUsage: 95.0);

        $result = $checker->check($metrics);

        $this->assertSame(HealthStatus::WARN, $result->status);
    }

    public function testCheckReturnsAllChecks(): void
    {
        $checker = new HealthChecker(new HealthThresholds());
        $metrics = $this->createMetrics();

        $result = $checker->check($metrics);

        $this->assertCount(4, $result->checks);
        $this->assertArrayHasKey('hitRate', $result->checks);
        $this->assertArrayHasKey('memoryWasted', $result->checks);
        $this->assertArrayHasKey('restarts', $result->checks);
        $this->assertArrayHasKey('memoryUsage', $result->checks);
    }

    public function testFromConfigCreatesInstance(): void
    {
        $config = [
            'min_hit_rate' => 85.0,
            'max_wasted_percent' => 15.0,
            'max_restarts' => 1,
            'max_memory_usage_percent' => 80.0,
        ];

        $checker = HealthChecker::fromConfig($config);

        $this->assertInstanceOf(HealthChecker::class, $checker);
    }

    public function testFromConfigUsesDefaultsForMissingKeys(): void
    {
        $config = [
            'min_hit_rate' => 85.0,
        ];

        $checker = HealthChecker::fromConfig($config);

        $this->assertInstanceOf(HealthChecker::class, $checker);
    }

    public function testWorstStatusDeterminesOverallStatus(): void
    {
        $checker = new HealthChecker(new HealthThresholds(
            minHitRate: 80.0,
            maxRestarts: 0,
        ));

        $metrics = $this->createMetrics(
            hitRate: 95.0,
            restarts: 1,
        );

        $result = $checker->check($metrics);

        $this->assertSame(HealthStatus::CRIT, $result->status);
    }

    private function createMetrics(
        float $hitRate = 95.0,
        float $memoryWasted = 0.0,
        int $restarts = 0,
        float $memoryUsage = 50.0,
    ): OpcacheMetrics {
        return new OpcacheMetrics(
            memoryUsage: $memoryUsage,
            memoryFree: 100.0 - $memoryUsage,
            memoryWasted: $memoryWasted,
            hitRate: $hitRate,
            cachedScripts: 10,
            cachedKeys: 15,
            maxCachedKeys: 10000,
            restarts: $restarts,
            startTime: 1234567890,
            lastRestartTime: null,
            scripts: [],
        );
    }
}
