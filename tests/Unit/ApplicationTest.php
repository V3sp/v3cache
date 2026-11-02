<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Application;
use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Exception\ValidationException;
use V3Cache\OpcacheManager\Health\HealthChecker;
use V3Cache\OpcacheManager\Service\OpcacheService;
use V3Cache\OpcacheManager\Storage\InMemoryMetricsStorage;

final class ApplicationTest extends TestCase
{
    public function testCreateReturnsApplicationInstance(): void
    {
        $path = $this->createTempConfig(['storage' => ['type' => 'memory']]);

        $app = Application::create($path);

        $this->assertInstanceOf(Application::class, $app);

        unlink($path);
    }

    public function testGetConfigReturnsParsedConfig(): void
    {
        $config = ['storage' => ['type' => 'memory']];
        $path = $this->createTempConfig($config);

        $app = Application::create($path);

        $this->assertSame($config, $app->getConfig());

        unlink($path);
    }

    public function testGetMetricsStorageReturnsStorage(): void
    {
        $path = $this->createTempConfig(['storage' => ['type' => 'memory']]);

        $app = Application::create($path);

        $this->assertInstanceOf(MetricsStorageInterface::class, $app->getMetricsStorage());

        unlink($path);
    }

    public function testGetOpcacheServiceReturnsService(): void
    {
        $path = $this->createTempConfig(['storage' => ['type' => 'memory']]);

        $app = Application::create($path);

        $this->assertInstanceOf(OpcacheService::class, $app->getOpcacheService());

        unlink($path);
    }

    public function testCreateDefaultsToMemoryStorageWhenStorageMissing(): void
    {
        $path = $this->createTempConfig([]);

        $app = Application::create($path);

        $this->assertInstanceOf(InMemoryMetricsStorage::class, $app->getMetricsStorage());

        unlink($path);
    }

    public function testGetHealthCheckerReturnsChecker(): void
    {
        $path = $this->createTempConfig(['storage' => ['type' => 'memory']]);

        $app = Application::create($path);

        $this->assertInstanceOf(HealthChecker::class, $app->getHealthChecker());

        unlink($path);
    }

    public function testCreateThrowsOnMissingFile(): void
    {
        $this->expectException(ValidationException::class);

        Application::create('/nonexistent/config.json');
    }

    public function testCreateThrowsOnInvalidConfig(): void
    {
        $path = $this->createTempConfig(['storage' => ['type' => 123]]);

        try {
            $this->expectException(ValidationException::class);
            Application::create($path);
        } finally {
            unlink($path);
        }
    }

    public function testCreateSafeLoadsJsonConfig(): void
    {
        $path = $this->createTempConfig(['storage' => ['type' => 'memory']]);

        try {
            $app = Application::createSafe($path);

            $this->assertInstanceOf(Application::class, $app);
        } finally {
            unlink($path);
        }
    }

    public function testCreateSafeRejectsPhpConfig(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg') . '.php';
        file_put_contents($path, '<?php return [];');

        try {
            $this->expectException(ValidationException::class);
            Application::createSafe($path);
        } finally {
            unlink($path);
        }
    }

    public function testGetConfigRedactedHidesRedisPassword(): void
    {
        $path = $this->createTempConfig([
            'storage' => [
                'type' => 'redis',
                'redis' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'password' => 'secret',
                    'db' => 0,
                ],
            ],
        ]);

        try {
            $app = Application::createSafe($path);

            $this->assertSame('secret', $app->getConfig()['storage']['redis']['password']);
            $this->assertSame('***', $app->getConfigRedacted()['storage']['redis']['password']);
        } finally {
            unlink($path);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTempConfig(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg') . '.json';
        file_put_contents($path, json_encode($config, JSON_THROW_ON_ERROR));

        return $path;
    }
}
