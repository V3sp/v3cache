<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\PhpConfigParser;

final class PhpConfigParserTest extends TestCase
{
    private PhpConfigParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PhpConfigParser();
    }

    public function testSupportsReturnsTrueForPhpExtension(): void
    {
        $this->assertTrue($this->parser->supports('config.php'));
    }

    public function testSupportsReturnsFalseForOtherExtensions(): void
    {
        $this->assertFalse($this->parser->supports('config.yaml'));
        $this->assertFalse($this->parser->supports('config.json'));
    }

    public function testParseReturnsArray(): void
    {
        $path = $this->createTempPhpConfig([
            'storage' => ['type' => 'memory'],
        ]);

        $result = $this->parser->parse($path);

        $this->assertSame(['storage' => ['type' => 'memory']], $result);

        unlink($path);
    }

    public function testParseThrowsOnMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->parser->parse('/nonexistent/config.php');
    }

    public function testParseThrowsOnInvalidPhp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg') . '.php';
        file_put_contents($path, '<?php return "not an array";');

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
    private function createTempPhpConfig(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfg');
        $content = '<?php return ' . var_export($config, true) . ';';
        file_put_contents($path, $content);

        return $path;
    }
}
