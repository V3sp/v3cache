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
        return self::fromConfig(ConfigLoaderFactory::create()->load($configPath));
    }

    /**
     * Safe bootstrap for untrusted config paths: JSON/YAML only.
     */
    public static function createSafe(string $configPath): self
    {
        return self::fromConfig(ConfigLoaderFactory::createSafe()->load($configPath));
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function fromConfig(array $config): self
    {
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

    /**
     * Returns the config with credentials replaced by a placeholder.
     *
     * @return array<string, mixed>
     */
    public function getConfigRedacted(): array
    {
        $config = $this->config;

        if (isset($config['storage']['redis']['password'])) {
            $config['storage']['redis']['password'] = '***';
        }

        if (isset($config['storage']['redis']['password_env'])) {
            $config['storage']['redis']['password_env'] = '***';
        }

        return $config;
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
