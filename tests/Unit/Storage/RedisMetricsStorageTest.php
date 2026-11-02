<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Exception\ValidationException;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;
use V3Cache\OpcacheManager\Storage\RedisMetricsStorage;

final class RedisMetricsStorageTest extends TestCase
{
    public function testRejectsInvalidHost(): void
    {
        $this->expectException(ValidationException::class);

        new RedisMetricsStorage(['host' => 'http://127.0.0.1']);
    }

    public function testRejectsInvalidPort(): void
    {
        $this->expectException(ValidationException::class);

        new RedisMetricsStorage(['port' => 70000]);
    }

    public function testRejectsInvalidDb(): void
    {
        $this->expectException(ValidationException::class);

        new RedisMetricsStorage(['db' => 99]);
    }

    public function testRejectsInvalidTls(): void
    {
        $this->expectException(ValidationException::class);

        new RedisMetricsStorage(['tls' => 'yes']);
    }

    public function testRejectsInvalidTimeout(): void
    {
        $this->expectException(ValidationException::class);

        new RedisMetricsStorage(['timeout' => 0]);
    }

    public function testSaveRejectsInvalidServerId(): void
    {
        $storage = new RedisMetricsStorage([]);

        $this->expectException(ValidationException::class);

        $storage->save('bad id', $this->createMetrics());
    }

    public function testLoadRejectsInvalidServerId(): void
    {
        $storage = new RedisMetricsStorage([]);

        $this->expectException(ValidationException::class);

        $storage->load('bad/id');
    }

    public function testDeleteRejectsInvalidServerId(): void
    {
        $storage = new RedisMetricsStorage([]);

        $this->expectException(ValidationException::class);

        $storage->delete('');
    }

    public function testAcceptsTlsConfig(): void
    {
        $storage = new RedisMetricsStorage([
            'tls' => true,
            'ssl' => ['verify_peer' => false],
            'timeout' => 1.5,
        ]);

        $this->assertInstanceOf(RedisMetricsStorage::class, $storage);
    }

    public function testAcceptsPasswordEnv(): void
    {
        putenv('V3CACHE_TEST_REDIS_PASSWORD=secret');

        try {
            $storage = new RedisMetricsStorage(['password_env' => 'V3CACHE_TEST_REDIS_PASSWORD']);
            $this->assertInstanceOf(RedisMetricsStorage::class, $storage);
        } finally {
            putenv('V3CACHE_TEST_REDIS_PASSWORD');
        }
    }

    private function createMetrics(): OpcacheMetrics
    {
        return new OpcacheMetrics(
            memoryUsage: 1.0,
            memoryFree: 2.0,
            memoryWasted: 0.0,
            hitRate: 99.0,
            cachedScripts: 1,
            cachedKeys: 1,
            maxCachedKeys: 100,
            restarts: 0,
            startTime: 1,
            lastRestartTime: null,
            scripts: [],
        );
    }
}
