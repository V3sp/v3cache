<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class JsonConfigParser implements ConfigParserInterface
{
    public function parse(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(\sprintf('Config file not found: %s', $path));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException(\sprintf('Failed to read config file: %s', $path));
        }

        try {
            $result = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(\sprintf('Invalid JSON in config file %s: %s', $path, $e->getMessage()));
        }

        if (!is_array($result)) {
            throw new \RuntimeException(\sprintf('Config file must contain a JSON object: %s', $path));
        }

        return $result;
    }

    public function supports(string $path): bool
    {
        return str_ends_with($path, '.json');
    }
}
