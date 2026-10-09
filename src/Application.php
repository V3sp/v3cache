<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager;

use V3Cache\OpcacheManager\Config\ConfigLoaderFactory;
use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Health\HealthChecker;
use V3Cache\OpcacheManager\Opcache\NativeOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;
use V3Cache\OpcacheManager\Storage\StorageFactory;

final class Application
{
    /**
     * @param array<string, mixed> $config
     */
    private function __construct(
        private readonly array $config,
        private readonly MetricsStorageInterface $storage,
        private readonly OpcacheService $service,
        private readonly HealthChecker $healthChecker,
    ) {
    }

    public static function create(string $configPath): self
    {
        $config = ConfigLoaderFactory::create()->load($configPath);

        $factory = new StorageFactory();
        $storage = $factory->create($config['storage'] ?? []);

        $service = new OpcacheService(new NativeOpcacheApi());
        $healthChecker = HealthChecker::fromConfig($config['health'] ?? []);

        return new self($config, $storage, $service, $healthChecker);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMetricsStorage(): MetricsStorageInterface
    {
        return $this->storage;
    }

    public function getOpcacheService(): OpcacheService
    {
        return $this->service;
    }

    public function getHealthChecker(): HealthChecker
    {
        return $this->healthChecker;
    }
}
