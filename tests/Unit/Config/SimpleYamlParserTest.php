<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\SimpleYamlParser;

final class SimpleYamlParserTest extends TestCase
{
    private SimpleYamlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SimpleYamlParser();
    }

    public function testParsesQuotedNumericAsString(): void
    {
        $result = $this->parser->parse("password: \"123\"\n");

        $this->assertSame('123', $result['password']);
    }

    public function testParsesFloat(): void
    {
        $result = $this->parser->parse("rate: 1.5\n");

        $this->assertSame(1.5, $result['rate']);
    }

    public function testParsesBoolean(): void
    {
        $result = $this->parser->parse("enabled: true\n");

        $this->assertTrue($result['enabled']);
    }

    public function testRejectsOversizedContent(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->parser->parse(str_repeat('a', 1048577));
    }
}
