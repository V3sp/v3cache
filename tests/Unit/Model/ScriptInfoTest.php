<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Model\ScriptInfo;

final class ScriptInfoTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $script = new ScriptInfo(
            path: '/var/www/app.php',
            memoryUsage: 1.5,
            hits: 100,
            lastUsed: 1234567890,
            timestamp: 1234567890,
        );

        $this->assertSame('/var/www/app.php', $script->path);
        $this->assertSame(1.5, $script->memoryUsage);
        $this->assertSame(100, $script->hits);
        $this->assertSame(1234567890, $script->lastUsed);
        $this->assertSame(1234567890, $script->timestamp);
    }

    public function testFromArrayCreatesInstance(): void
    {
        $data = [
            'path' => '/var/www/test.php',
            'memory_usage' => 2.5,
            'hits' => 50,
            'last_used' => 1234567890,
            'timestamp' => 1234567890,
        ];

        $script = ScriptInfo::fromArray($data);

        $this->assertSame('/var/www/test.php', $script->path);
        $this->assertSame(2.5, $script->memoryUsage);
        $this->assertSame(50, $script->hits);
        $this->assertSame(1234567890, $script->lastUsed);
        $this->assertSame(1234567890, $script->timestamp);
    }

    public function testFromArrayThrowsOnMissingKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScriptInfo::fromArray(['path' => '/var/www/test.php']);
    }

    public function testToArrayReturnsSnakeCase(): void
    {
        $script = new ScriptInfo(
            path: '/var/www/app.php',
            memoryUsage: 1.5,
            hits: 100,
            lastUsed: 1234567890,
            timestamp: 1234567890,
        );

        $this->assertSame([
            'path' => '/var/www/app.php',
            'memory_usage' => 1.5,
            'hits' => 100,
            'last_used' => 1234567890,
            'timestamp' => 1234567890,
        ], $script->toArray());
    }
}
