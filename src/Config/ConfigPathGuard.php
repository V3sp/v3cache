<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

use V3Cache\OpcacheManager\Exception\ValidationException;

final class ConfigPathGuard
{
    private const MAX_BYTES = 1048576;

    public static function resolve(string $path): string
    {
        if ($path === '' || str_contains($path, "\0")) {
            throw new ValidationException('Config path is empty or contains a NUL byte');
        }

        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $path) === 1) {
            throw new ValidationException('Stream wrappers are not allowed for config paths');
        }

        $real = realpath($path);

        if ($real === false || !is_file($real) || !is_readable($real)) {
            throw new ValidationException(\sprintf('Config file not found or not readable: %s', $path));
        }

        $size = filesize($real);

        if ($size === false || $size > self::MAX_BYTES) {
            throw new ValidationException(\sprintf('Config file is too large: %s', $path));
        }

        return $real;
    }
}
