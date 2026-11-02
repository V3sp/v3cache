<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\ConfigPathGuard;
use V3Cache\OpcacheManager\Exception\ValidationException;

final class ConfigPathGuardTest extends TestCase
{
    public function testResolvesExistingFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg_');
        self::assertNotFalse($path);
        file_put_contents($path, '<?php return [];');

        try {
            $this->assertSame(realpath($path), ConfigPathGuard::resolve($path));
        } finally {
            unlink($path);
        }
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(ValidationException::class);

        ConfigPathGuard::resolve('/nonexistent/config.json');
    }

    public function testRejectsDirectory(): void
    {
        $this->expectException(ValidationException::class);

        ConfigPathGuard::resolve(sys_get_temp_dir());
    }

    public function testRejectsStreamWrapper(): void
    {
        $this->expectException(ValidationException::class);

        ConfigPathGuard::resolve('php://memory');
    }

    public function testRejectsNulByte(): void
    {
        $this->expectException(ValidationException::class);

        ConfigPathGuard::resolve("config\0.json");
    }

    public function testRejectsOversizedFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg_');
        self::assertNotFalse($path);
        file_put_contents($path, str_repeat('a', 1048577));

        try {
            $this->expectException(ValidationException::class);
            ConfigPathGuard::resolve($path);
        } finally {
            unlink($path);
        }
    }
}
