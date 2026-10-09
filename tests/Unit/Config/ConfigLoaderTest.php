<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\ConfigLoader;
use V3Cache\OpcacheManager\Config\ConfigLoaderFactory;
use V3Cache\OpcacheManager\Exception\ValidationException;

final class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;

    protected function setUp(): void
    {
        $this->loader = ConfigLoaderFactory::create();
    }

    public function testLoadReturnsParsedConfig(): void
    {
        $path = $this->createTempJsonConfig([
            'storage' => ['type' => 'memory'],
        ]);

        $result = $this->loader->load($path);

        $this->assertSame(['storage' => ['type' => 'memory']], $result);

        unlink($path);
    }

    public function testLoadParsesYamlUsingDefaultParserRegistry(): void
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cfg_');
        self::assertNotFalse($temporaryPath);
        $path = $temporaryPath . '.yaml';
        rename($temporaryPath, $path);
        file_put_contents($path, "storage:\n  type: memory\n");

        try {
            $config = $this->loader->load($path);

            $this->assertSame('memory', $config['storage']['type']);
        } finally {
            unlink($path);
        }
    }

    public function testLoadThrowsOnMissingFile(): void
    {
        $this->expectException(ValidationException::class);

        $this->loader->load('/nonexistent/config.json');
    }

    public function testLoadThrowsOnInvalidConfig(): void
    {
        $path = $this->createTempJsonConfig([
            'storage' => ['type' => 123],
        ]);

        try {
            $this->expectException(ValidationException::class);
            $this->loader->load($path);
        } finally {
            unlink($path);
        }
    }

    public function testLoadThrowsOnUnknownExtension(): void
    {
        $this->expectException(ValidationException::class);

        $this->loader->load('config.xml');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTempJsonConfig(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg') . '.json';
        file_put_contents($path, json_encode($config, JSON_THROW_ON_ERROR));

        return $path;
    }
}
