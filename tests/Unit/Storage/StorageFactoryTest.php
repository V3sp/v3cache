<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Exception\ValidationException;
use V3Cache\OpcacheManager\Storage\InMemoryMetricsStorage;
use V3Cache\OpcacheManager\Storage\RedisMetricsStorage;
use V3Cache\OpcacheManager\Storage\StorageFactory;

final class StorageFactoryTest extends TestCase
{
    private StorageFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new StorageFactory();
    }

    public function testCreateReturnsInMemoryStorageForMemoryType(): void
    {
        $storage = $this->factory->create(['type' => 'memory']);

        $this->assertInstanceOf(InMemoryMetricsStorage::class, $storage);
    }

    public function testCreateReturnsRedisStorageForRedisType(): void
    {
        $storage = $this->factory->create([
            'type' => 'redis',
            'redis' => ['host' => '127.0.0.1', 'port' => 6379],
        ]);

        $this->assertInstanceOf(RedisMetricsStorage::class, $storage);
    }

    public function testCreateThrowsOnUnknownType(): void
    {
        $this->expectException(ValidationException::class);

        $this->factory->create(['type' => 'unknown']);
    }

    public function testCreateDefaultsToMemoryWhenTypeMissing(): void
    {
        $storage = $this->factory->create([]);

        $this->assertInstanceOf(InMemoryMetricsStorage::class, $storage);
    }
}
