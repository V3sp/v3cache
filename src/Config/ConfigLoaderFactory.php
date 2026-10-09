<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class ConfigLoaderFactory
{
    public static function create(): ConfigLoader
    {
        $registry = new ConfigParserRegistry([
            new PhpConfigParser(),
            new YamlConfigParser(),
            new JsonConfigParser(),
        ]);

        return new ConfigLoader($registry, new ConfigValidator(new ConfigSchema()));
    }
}
