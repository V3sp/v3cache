<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\YamlConfigParser;

final class YamlConfigParserTest extends TestCase
{
    private YamlConfigParser $parser;

    protected function setUp(): void
    {
        $this->parser = new YamlConfigParser();
    }

    public function testSupportsReturnsTrueForYamlExtension(): void
    {
        $this->assertTrue($this->parser->supports('config.yaml'));
        $this->assertTrue($this->parser->supports('config.yml'));
    }

    public function testSupportsReturnsFalseForOtherExtensions(): void
    {
        $this->assertFalse($this->parser->supports('config.php'));
        $this->assertFalse($this->parser->supports('config.json'));
    }

    public function testParseReturnsArray(): void
    {
        $path = $this->createTempYamlConfig([
            'storage' => ['type' => 'memory'],
        ]);

        $result = $this->parser->parse($path);

        $this->assertSame(['storage' => ['type' => 'memory']], $result);

        unlink($path);
    }

    public function testParseThrowsOnMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->parser->parse('/nonexistent/config.yaml');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTempYamlConfig(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg');
        $yaml = "storage:\n  type: memory\n";
        file_put_contents($path, $yaml);

        return $path;
    }
}
