<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class PhpConfigParser implements ConfigParserInterface
{
    public function parse(string $path): array
    {
        $resolvedPath = ConfigPathGuard::resolve($path);

        // Config files are trusted code. Isolate the include scope so the file
        // cannot access $this or local variables of the parser.
        $result = (static function (string $__path): mixed {
            return include $__path;
        })($resolvedPath);

        if (!is_array($result)) {
            throw new \RuntimeException(\sprintf('Config file must return an array: %s', $resolvedPath));
        }

        return $result;
    }

    public function supports(string $path): bool
    {
        return str_ends_with($path, '.php');
    }
}
