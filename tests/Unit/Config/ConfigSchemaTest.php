<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\ConfigSchema;

final class ConfigSchemaTest extends TestCase
{
    public function testGetSectionsReturnsAllSections(): void
    {
        $schema = new ConfigSchema();

        $sections = $schema->getSections();

        $this->assertContains('storage', $sections);
        $this->assertContains('health', $sections);
        $this->assertContains('servers', $sections);
        $this->assertContains('optimization', $sections);
        $this->assertContains('panel', $sections);
    }

    public function testGetKeysReturnsKeysForSection(): void
    {
        $schema = new ConfigSchema();

        $keys = $schema->getKeys('storage');

        $this->assertContains('type', $keys);
        $this->assertContains('redis', $keys);
    }

    public function testGetKeysThrowsOnUnknownSection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $schema = new ConfigSchema();
        $schema->getKeys('unknown');
    }

    public function testGetDefaultValueReturnsDefault(): void
    {
        $schema = new ConfigSchema();

        $this->assertSame('memory', $schema->getDefaultValue('storage.type'));
    }

    public function testGetDefaultValueReturnsNullWhenNoDefault(): void
    {
        $schema = new ConfigSchema();

        $this->assertNull($schema->getDefaultValue('storage.redis'));
    }

    public function testIsRequiredReturnsFalseForStorageType(): void
    {
        $schema = new ConfigSchema();

        $this->assertFalse($schema->isRequired('storage.type'));
    }

    public function testIsRequiredReturnsFalseForOptionalKey(): void
    {
        $schema = new ConfigSchema();

        $this->assertFalse($schema->isRequired('panel.poll_interval'));
    }

    public function testIsRequiredThrowsOnUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $schema = new ConfigSchema();
        $schema->isRequired('unknown.key');
    }

    public function testGetTypeReturnsTypeForKey(): void
    {
        $schema = new ConfigSchema();

        $this->assertSame('string', $schema->getType('storage.type'));
    }

    public function testGetTypeThrowsOnUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $schema = new ConfigSchema();
        $schema->getType('unknown.key');
    }

    public function testIsOpenReturnsTrueForServers(): void
    {
        $schema = new ConfigSchema();

        $this->assertTrue($schema->isOpen('servers'));
    }

    public function testIsOpenReturnsFalseForOtherSections(): void
    {
        $schema = new ConfigSchema();

        $this->assertFalse($schema->isOpen('storage'));
        $this->assertFalse($schema->isOpen('health'));
    }
}
