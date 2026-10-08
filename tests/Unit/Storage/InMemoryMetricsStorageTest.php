<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;
use V3Cache\OpcacheManager\Storage\InMemoryMetricsStorage;

final class InMemoryMetricsStorageTest extends TestCase
{
    private InMemoryMetricsStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryMetricsStorage();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(MetricsStorageInterface::class, $this->storage);
    }

    public function testSaveAndLoad(): void
    {
        $metrics = $this->createMetrics();
        $this->storage->save('server-1', $metrics);

        $loaded = $this->storage->load('server-1');

        $this->assertSame($metrics, $loaded);
    }

    public function testLoadReturnsNullForUnknownServer(): void
    {
        $this->assertNull($this->storage->load('unknown'));
    }

    public function testLoadAllReturnsAllSavedMetrics(): void
    {
        $metrics1 = $this->createMetrics();
        $metrics2 = $this->createMetrics();

        $this->storage->save('server-1', $metrics1);
        $this->storage->save('server-2', $metrics2);

        $all = $this->storage->loadAll();

        $this->assertCount(2, $all);
        $this->assertSame($metrics1, $all['server-1']);
        $this->assertSame($metrics2, $all['server-2']);
    }

    public function testDeleteRemovesMetrics(): void
    {
        $metrics = $this->createMetrics();
        $this->storage->save('server-1', $metrics);

        $this->storage->delete('server-1');

        $this->assertNull($this->storage->load('server-1'));
    }

    public function testDeleteOnUnknownServerDoesNothing(): void
    {
        $this->storage->delete('unknown');

        $this->assertNull($this->storage->load('unknown'));
    }

    public function testSaveOverwritesExistingMetrics(): void
    {
        $metrics1 = $this->createMetrics();
        $metrics2 = $this->createMetrics();

        $this->storage->save('server-1', $metrics1);
        $this->storage->save('server-1', $metrics2);

        $loaded = $this->storage->load('server-1');

        $this->assertSame($metrics2, $loaded);
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
