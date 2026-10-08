<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class PhpConfigParser implements ConfigParserInterface
{
    public function parse(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(\sprintf('Config file not found: %s', $path));
        }

        $result = include $path;

        if (!is_array($result)) {
            throw new \RuntimeException(\sprintf('Config file must return an array: %s', $path));
        }

        return $result;
    }

    public function supports(string $path): bool
    {
        return str_ends_with($path, '.php');
    }
}
