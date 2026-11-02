<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class ConfigLoaderFactory
{
    public static function create(): ConfigLoader
    {
        return self::createWith([
            new PhpConfigParser(),
            new YamlConfigParser(),
            new JsonConfigParser(),
        ]);
    }

    /**
     * Safe loader for untrusted paths: JSON/YAML only, never PHP includes.
     */
    public static function createSafe(): ConfigLoader
    {
        return self::createWith([
            new YamlConfigParser(),
            new JsonConfigParser(),
        ]);
    }

    /**
     * @param array<int, ConfigParserInterface> $parsers
     */
    private static function createWith(array $parsers): ConfigLoader
    {
        $registry = new ConfigParserRegistry($parsers);

        return new ConfigLoader($registry, new ConfigValidator(new ConfigSchema()));
    }
}
