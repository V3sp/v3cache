<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\ConfigParserInterface;
use V3Cache\OpcacheManager\Config\ConfigParserRegistry;
use V3Cache\OpcacheManager\Config\JsonConfigParser;
use V3Cache\OpcacheManager\Config\PhpConfigParser;
use V3Cache\OpcacheManager\Config\YamlConfigParser;
use V3Cache\OpcacheManager\Exception\ValidationException;

final class ConfigParserRegistryTest extends TestCase
{
    private ConfigParserRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ConfigParserRegistry([
            new PhpConfigParser(),
            new YamlConfigParser(),
            new JsonConfigParser(),
        ]);
    }

    public function testForReturnsPhpParser(): void
    {
        $parser = $this->registry->for('config.php');

        $this->assertInstanceOf(PhpConfigParser::class, $parser);
    }

    public function testForReturnsYamlParser(): void
    {
        $parser = $this->registry->for('config.yaml');

        $this->assertInstanceOf(YamlConfigParser::class, $parser);
    }

    public function testForReturnsJsonParser(): void
    {
        $parser = $this->registry->for('config.json');

        $this->assertInstanceOf(JsonConfigParser::class, $parser);
    }

    public function testForThrowsOnUnknownExtension(): void
    {
        $this->expectException(ValidationException::class);

        $this->registry->for('config.xml');
    }

    public function testForThrowsOnNoExtension(): void
    {
        $this->expectException(ValidationException::class);

        $this->registry->for('config');
    }
}
