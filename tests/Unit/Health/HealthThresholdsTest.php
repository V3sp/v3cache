<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Health;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Health\HealthThresholds;

final class HealthThresholdsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $thresholds = new HealthThresholds();

        $this->assertSame(80.0, $thresholds->minHitRate);
        $this->assertSame(20.0, $thresholds->maxWastedPercent);
        $this->assertSame(0, $thresholds->maxRestarts);
        $this->assertSame(90.0, $thresholds->maxMemoryUsagePercent);
    }

    public function testCustomValues(): void
    {
        $thresholds = new HealthThresholds(
            minHitRate: 90.0,
            maxWastedPercent: 10.0,
            maxRestarts: 1,
            maxMemoryUsagePercent: 80.0,
        );

        $this->assertSame(90.0, $thresholds->minHitRate);
        $this->assertSame(10.0, $thresholds->maxWastedPercent);
        $this->assertSame(1, $thresholds->maxRestarts);
        $this->assertSame(80.0, $thresholds->maxMemoryUsagePercent);
    }

    public function testFromConfigCreatesInstance(): void
    {
        $config = [
            'min_hit_rate' => 85.0,
            'max_wasted_percent' => 15.0,
            'max_restarts' => 2,
            'max_memory_usage_percent' => 75.0,
        ];

        $thresholds = HealthThresholds::fromConfig($config);

        $this->assertSame(85.0, $thresholds->minHitRate);
        $this->assertSame(15.0, $thresholds->maxWastedPercent);
        $this->assertSame(2, $thresholds->maxRestarts);
        $this->assertSame(75.0, $thresholds->maxMemoryUsagePercent);
    }

    public function testFromConfigUsesDefaultsForMissingKeys(): void
    {
        $config = [
            'min_hit_rate' => 85.0,
        ];

        $thresholds = HealthThresholds::fromConfig($config);

        $this->assertSame(85.0, $thresholds->minHitRate);
        $this->assertSame(20.0, $thresholds->maxWastedPercent);
        $this->assertSame(0, $thresholds->maxRestarts);
        $this->assertSame(90.0, $thresholds->maxMemoryUsagePercent);
    }
}
