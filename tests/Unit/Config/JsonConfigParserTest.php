<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\JsonConfigParser;

final class JsonConfigParserTest extends TestCase
{
    private JsonConfigParser $parser;

    protected function setUp(): void
    {
        $this->parser = new JsonConfigParser();
    }

    public function testSupportsReturnsTrueForJsonExtension(): void
    {
        $this->assertTrue($this->parser->supports('config.json'));
    }

    public function testSupportsReturnsFalseForOtherExtensions(): void
    {
        $this->assertFalse($this->parser->supports('config.php'));
        $this->assertFalse($this->parser->supports('config.yaml'));
    }

    public function testParseReturnsArray(): void
    {
        $path = $this->createTempJsonConfig([
            'storage' => ['type' => 'memory'],
        ]);

        $result = $this->parser->parse($path);

        $this->assertSame(['storage' => ['type' => 'memory']], $result);

        unlink($path);
    }

    public function testParseThrowsOnMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->parser->parse('/nonexistent/config.json');
    }

    public function testParseThrowsOnInvalidJson(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg') . '.json';
        file_put_contents($path, '{invalid json}');

        try {
            $this->expectException(\RuntimeException::class);
            $this->parser->parse($path);
        } finally {
            unlink($path);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTempJsonConfig(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($path, json_encode($config, JSON_THROW_ON_ERROR));

        return $path;
    }
}
