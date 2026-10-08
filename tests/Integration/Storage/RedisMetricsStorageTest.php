<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Integration\Storage;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;
use V3Cache\OpcacheManager\Storage\RedisMetricsStorage;

final class RedisMetricsStorageTest extends TestCase
{
    private RedisMetricsStorage $storage;

    protected function setUp(): void
    {
        if (!class_exists(\Predis\Client::class)) {
            self::markTestSkipped('predis/predis is not installed');
        }

        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('REDIS_PORT') ?: 6379);

        try {
            $client = new \Predis\Client(['scheme' => 'tcp', 'host' => $host, 'port' => $port]);
            $client->ping();
        } catch (\Throwable $e) {
            self::markTestSkipped('Redis is not reachable: ' . $e->getMessage());
        }

        $this->storage = new RedisMetricsStorage(['host' => $host, 'port' => $port]);
    }

    protected function tearDown(): void
    {
        if (isset($this->storage)) {
            $this->storage->delete('server-1');
            $this->storage->delete('server-2');
        }
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(MetricsStorageInterface::class, $this->storage);
    }

    public function testSaveAndLoadRoundTripsMetrics(): void
    {
        $metrics = $this->createMetrics();

        $this->storage->save('server-1', $metrics);

        $loaded = $this->storage->load('server-1');

        $this->assertInstanceOf(OpcacheMetrics::class, $loaded);
        $this->assertSame($metrics->memoryUsage, $loaded->memoryUsage);
        $this->assertSame($metrics->hitRate, $loaded->hitRate);
        $this->assertSame($metrics->cachedScripts, $loaded->cachedScripts);
        $this->assertSame($metrics->lastRestartTime, $loaded->lastRestartTime);
    }

    public function testLoadReturnsNullForUnknownServer(): void
    {
        $this->assertNull($this->storage->load('unknown-server'));
    }

    public function testLoadAllReturnsAllSavedMetrics(): void
    {
        $this->storage->save('server-1', $this->createMetrics());
        $this->storage->save('server-2', $this->createMetrics());

        $all = $this->storage->loadAll();

        $this->assertArrayHasKey('server-1', $all);
        $this->assertArrayHasKey('server-2', $all);
        $this->assertInstanceOf(OpcacheMetrics::class, $all['server-1']);
    }

    public function testDeleteRemovesMetrics(): void
    {
        $this->storage->save('server-1', $this->createMetrics());

        $this->storage->delete('server-1');

        $this->assertNull($this->storage->load('server-1'));
    }

    public function testSaveAppliesTtl(): void
    {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('REDIS_PORT') ?: 6379);
        $client = new \Predis\Client(['scheme' => 'tcp', 'host' => $host, 'port' => $port]);

        $this->storage->save('server-1', $this->createMetrics());

        $ttl = (int) $client->ttl('opcache:metrics:server-1');

        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(3600, $ttl);
    }

    private function createMetrics(): OpcacheMetrics
    {
        return new OpcacheMetrics(
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
            scripts: [],
        );
    }
}
