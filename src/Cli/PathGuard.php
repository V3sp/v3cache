<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

final class PathGuard
{
    private const MAX_FILE_BYTES = 8388608;
    private const MAX_LIST_BYTES = 1048576;

    /**
     * @param array<int, string> $allowedRoots
     */
    public static function resolveFile(string $path, array $allowedRoots): ?string
    {
        $real = self::resolveExisting($path);

        if ($real === null || !is_file($real) || is_link($path)) {
            return null;
        }

        if (!self::isWithinRoots($real, $allowedRoots)) {
            return null;
        }

        $size = filesize($real);

        if ($size === false || $size > self::MAX_FILE_BYTES) {
            return null;
        }

        return $real;
    }

    /**
     * @param array<int, string> $allowedRoots
     */
    public static function resolveDirectory(string $path, array $allowedRoots): ?string
    {
        $real = self::resolveExisting($path);

        if ($real === null || !is_dir($real) || !self::isWithinRoots($real, $allowedRoots)) {
            return null;
        }

        return $real;
    }

    public static function resolveListFile(string $path): ?string
    {
        $real = self::resolveExisting($path);

        if ($real === null || !is_file($real) || !is_readable($real)) {
            return null;
        }

        $size = filesize($real);

        if ($size === false || $size > self::MAX_LIST_BYTES) {
            return null;
        }

        return $real;
    }

    /**
     * @param array<int, string> $roots
     * @return array<int, string>
     */
    public static function normalizeRoots(array $roots): array
    {
        $normalized = [];

        foreach ($roots as $root) {
            $real = realpath($root);

            if ($real !== false && is_dir($real)) {
                $normalized[] = rtrim($real, DIRECTORY_SEPARATOR);
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int, string> $roots
     */
    public static function isWithinRoots(string $realPath, array $roots): bool
    {
        foreach (self::normalizeRoots($roots) as $root) {
            if ($realPath === $root || str_starts_with($realPath, $root . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private static function resolveExisting(string $path): ?string
    {
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $path) === 1) {
            return null;
        }

        $real = realpath($path);

        if ($real === false || !is_readable($real)) {
            return null;
        }

        return $real;
    }
}
