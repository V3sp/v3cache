<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Health\HealthStatus;
use V3Cache\OpcacheManager\Model\OptimizationRecommendation;

final class OptimizationRecommendationTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $recommendation = new OptimizationRecommendation(
            directive: 'opcache.memory_consumption',
            currentValue: '64',
            recommendedValue: '128',
            severity: HealthStatus::WARN,
            description: 'Increase memory consumption',
        );

        $this->assertSame('opcache.memory_consumption', $recommendation->directive);
        $this->assertSame('64', $recommendation->currentValue);
        $this->assertSame('128', $recommendation->recommendedValue);
        $this->assertSame(HealthStatus::WARN, $recommendation->severity);
        $this->assertSame('Increase memory consumption', $recommendation->description);
    }

    public function testFromArrayCreatesInstance(): void
    {
        $data = [
            'directive' => 'opcache.memory_consumption',
            'current_value' => '64',
            'recommended_value' => '128',
            'severity' => 'warn',
            'description' => 'Increase memory consumption',
        ];

        $recommendation = OptimizationRecommendation::fromArray($data);

        $this->assertSame('opcache.memory_consumption', $recommendation->directive);
        $this->assertSame('64', $recommendation->currentValue);
        $this->assertSame('128', $recommendation->recommendedValue);
        $this->assertSame(HealthStatus::WARN, $recommendation->severity);
        $this->assertSame('Increase memory consumption', $recommendation->description);
    }

    public function testFromArrayThrowsOnMissingKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OptimizationRecommendation::fromArray(['directive' => 'opcache.memory_consumption']);
    }
}
