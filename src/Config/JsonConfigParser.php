<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class JsonConfigParser implements ConfigParserInterface
{
    public function parse(string $path): array
    {
        $resolvedPath = ConfigPathGuard::resolve($path);

        $content = file_get_contents($resolvedPath);
        if ($content === false) {
            throw new \RuntimeException(\sprintf('Failed to read config file: %s', $resolvedPath));
        }

        try {
            $result = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(
                \sprintf('Invalid JSON in config file %s: %s', $resolvedPath, $e->getMessage()),
            );
        }

        if (!is_array($result)) {
            throw new \RuntimeException(\sprintf('Config file must contain a JSON object: %s', $resolvedPath));
        }

        return $result;
    }

    public function supports(string $path): bool
    {
        return str_ends_with($path, '.json');
    }
}
