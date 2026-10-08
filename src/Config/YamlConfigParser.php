<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class YamlConfigParser implements ConfigParserInterface
{
    public function __construct(
        private readonly SimpleYamlParser $parser = new SimpleYamlParser(),
    ) {
    }

    public function parse(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(\sprintf('Config file not found: %s', $path));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException(\sprintf('Failed to read config file: %s', $path));
        }

        $result = $this->parser->parse($content);

        return $result;
    }

    public function supports(string $path): bool
    {
        return str_ends_with($path, '.yaml') || str_ends_with($path, '.yml');
    }
}
