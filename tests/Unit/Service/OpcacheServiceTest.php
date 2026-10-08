<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Exception\OpcacheNotEnabledException;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;
use V3Cache\OpcacheManager\Model\OptimizationRecommendation;
use V3Cache\OpcacheManager\Model\ScriptInfo;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class OpcacheServiceTest extends TestCase
{
    private MockOpcacheApi $api;
    private OpcacheService $service;

    protected function setUp(): void
    {
        $this->api = new MockOpcacheApi();
        $this->service = new OpcacheService($this->api);
    }

    public function testGetMetricsThrowsWhenOpcacheNotEnabled(): void
    {
        $this->expectException(OpcacheNotEnabledException::class);

        $this->service->getMetrics();
    }

    public function testGetMetricsReturnsMetrics(): void
    {
        $this->api->setEnabled(true);
        $this->api->setStatus($this->createStatus());

        $metrics = $this->service->getMetrics();

        $this->assertInstanceOf(OpcacheMetrics::class, $metrics);
        $this->assertSame(64.0, $metrics->memoryUsage);
        $this->assertSame(36.0, $metrics->memoryFree);
        $this->assertSame(0.0, $metrics->memoryWasted);
        $this->assertSame(95.2, $metrics->hitRate);
        $this->assertSame(10, $metrics->cachedScripts);
        $this->assertSame(15, $metrics->cachedKeys);
        $this->assertSame(10000, $metrics->maxCachedKeys);
        $this->assertSame(0, $metrics->restarts);
        $this->assertSame(1234567890, $metrics->startTime);
        $this->assertNull($metrics->lastRestartTime);
        $this->assertCount(1, $metrics->scripts);
    }

    public function testGetScriptsReturnsScripts(): void
    {
        $this->api->setEnabled(true);
        $this->api->setStatus($this->createStatus());

        $scripts = $this->service->getScripts();

        $this->assertCount(1, $scripts);
        $this->assertInstanceOf(ScriptInfo::class, $scripts[0]);
        $this->assertSame('/var/www/app.php', $scripts[0]->path);
        $this->assertSame(1.0, $scripts[0]->memoryUsage);
        $this->assertSame(10, $scripts[0]->hits);
    }

    public function testReloadCallsReset(): void
    {
        $this->api->setEnabled(true);

        $result = $this->service->reload();

        $this->assertTrue($result);
        $this->assertContains('reset', $this->api->getCalls());
    }

    public function testInvalidateCallsApi(): void
    {
        $this->api->setEnabled(true);

        $result = $this->service->invalidate('/path/to/file.php');

        $this->assertTrue($result);
        $this->assertContains('invalidate', $this->api->getCalls());
    }

    public function testCompileFileCallsApi(): void
    {
        $this->api->setEnabled(true);

        $result = $this->service->compileFile('/path/to/file.php');

        $this->assertTrue($result);
        $this->assertContains('compileFile', $this->api->getCalls());
    }

    public function testGetSettingsReturnsConfiguration(): void
    {
        $this->api->setEnabled(true);

        $settings = $this->service->getSettings();

        $this->assertIsArray($settings);
    }

    public function testOptimizeReturnsRecommendations(): void
    {
        $this->api->setEnabled(true);

        $recommendations = $this->service->optimize();

        $this->assertIsArray($recommendations);
        foreach ($recommendations as $recommendation) {
            $this->assertInstanceOf(OptimizationRecommendation::class, $recommendation);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createStatus(): array
    {
        return [
            'opcache_enabled' => true,
            'cache_full' => false,
            'memory_usage' => [
                'used_memory' => 67108864,
                'free_memory' => 37748736,
                'wasted_memory' => 0,
                'current_wasted_percentage' => 0.0,
            ],
            'opcache_statistics' => [
                'num_cached_scripts' => 10,
                'num_cached_keys' => 15,
                'max_cached_keys' => 10000,
                'hits' => 100,
                'misses' => 5,
                'blacklist_misses' => 0,
                'blacklist_miss_ratio' => 0.0,
                'opcache_hit_rate' => 95.2,
                'start_time' => 1234567890,
                'last_restart_time' => null,
                'oom_restarts' => 0,
                'hash_restarts' => 0,
                'manual_restarts' => 0,
            ],
            'scripts' => [
                '/var/www/app.php' => [
                    'full_path' => '/var/www/app.php',
                    'hits' => 10,
                    'memory_consumption' => 1048576,
                    'last_used' => 1234567890,
                    'timestamp' => 1234567890,
                ],
            ],
        ];
    }
}
