<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;
use V3Cache\OpcacheManager\Model\ScriptInfo;

final class OpcacheMetricsTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $scripts = [
            new ScriptInfo('/var/www/a.php', 1.0, 10, 1234567890, 1234567890),
        ];

        $metrics = new OpcacheMetrics(
            memoryUsage: 64.5,
            memoryFree: 35.5,
            memoryWasted: 0.0,
            hitRate: 95.5,
            cachedScripts: 10,
            cachedKeys: 15,
            maxCachedKeys: 10000,
            restarts: 0,
            startTime: 1234567890,
            lastRestartTime: null,
            scripts: $scripts,
        );

        $this->assertSame(64.5, $metrics->memoryUsage);
        $this->assertSame(35.5, $metrics->memoryFree);
        $this->assertSame(0.0, $metrics->memoryWasted);
        $this->assertSame(95.5, $metrics->hitRate);
        $this->assertSame(10, $metrics->cachedScripts);
        $this->assertSame(15, $metrics->cachedKeys);
        $this->assertSame(10000, $metrics->maxCachedKeys);
        $this->assertSame(0, $metrics->restarts);
        $this->assertSame(1234567890, $metrics->startTime);
        $this->assertNull($metrics->lastRestartTime);
        $this->assertSame($scripts, $metrics->scripts);
    }

    public function testFromArrayCreatesInstance(): void
    {
        $data = [
            'memory_usage' => 64.5,
            'memory_free' => 35.5,
            'memory_wasted' => 0.0,
            'hit_rate' => 95.5,
            'cached_scripts' => 10,
            'cached_keys' => 15,
            'max_cached_keys' => 10000,
            'restarts' => 0,
            'start_time' => 1234567890,
            'last_restart_time' => null,
            'scripts' => [
                [
                    'path' => '/var/www/a.php',
                    'memory_usage' => 1.0,
                    'hits' => 10,
                    'last_used' => 1234567890,
                    'timestamp' => 1234567890,
                ],
            ],
        ];

        $metrics = OpcacheMetrics::fromArray($data);

        $this->assertSame(64.5, $metrics->memoryUsage);
        $this->assertSame(35.5, $metrics->memoryFree);
        $this->assertSame(0.0, $metrics->memoryWasted);
        $this->assertSame(95.5, $metrics->hitRate);
        $this->assertSame(10, $metrics->cachedScripts);
        $this->assertSame(15, $metrics->cachedKeys);
        $this->assertSame(10000, $metrics->maxCachedKeys);
        $this->assertSame(0, $metrics->restarts);
        $this->assertSame(1234567890, $metrics->startTime);
        $this->assertNull($metrics->lastRestartTime);
        $this->assertCount(1, $metrics->scripts);
        $this->assertSame('/var/www/a.php', $metrics->scripts[0]->path);
    }

    public function testFromArrayThrowsOnMissingKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OpcacheMetrics::fromArray(['memory_usage' => 64.5]);
    }

    public function testToArrayRoundTripsThroughFromArray(): void
    {
        $metrics = new OpcacheMetrics(
            memoryUsage: 64.5,
            memoryFree: 35.5,
            memoryWasted: 0.0,
            hitRate: 95.5,
            cachedScripts: 10,
            cachedKeys: 15,
            maxCachedKeys: 10000,
            restarts: 0,
            startTime: 1234567890,
            lastRestartTime: null,
            scripts: [new ScriptInfo('/var/www/a.php', 1.0, 10, 1234567890, 1234567890)],
        );

        $data = $metrics->toArray();

        $this->assertSame(64.5, $data['memory_usage']);
        $this->assertSame(95.5, $data['hit_rate']);
        $this->assertArrayHasKey('scripts', $data);
        $this->assertSame('/var/www/a.php', $data['scripts'][0]['path']);

        $roundTripped = OpcacheMetrics::fromArray($data);

        $this->assertSame($metrics->memoryUsage, $roundTripped->memoryUsage);
        $this->assertSame($metrics->hitRate, $roundTripped->hitRate);
        $this->assertSame($metrics->lastRestartTime, $roundTripped->lastRestartTime);
        $this->assertCount(1, $roundTripped->scripts);
        $this->assertSame('/var/www/a.php', $roundTripped->scripts[0]->path);
    }
}
