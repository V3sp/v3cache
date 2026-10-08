<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Opcache;

use V3Cache\OpcacheManager\Exception\OpcacheApiException;

final class NativeOpcacheApi implements OpcacheApiInterface
{
    public function isEnabled(): bool
    {
        if (ini_get('opcache.enable') !== '1') {
            return false;
        }

        return opcache_get_status() !== false;
    }

    public function getStatus(bool $withScripts = false): ?array
    {
        $status = opcache_get_status($withScripts);

        if ($status === false) {
            return null;
        }

        return $status;
    }

    public function reset(): bool
    {
        return opcache_reset();
    }

    public function invalidate(string $path, bool $force = true): bool
    {
        return opcache_invalidate($path, $force);
    }

    public function compileFile(string $path): bool
    {
        return opcache_compile_file($path);
    }

    public function getConfiguration(): array
    {
        $config = opcache_get_configuration();

        if ($config === false) {
            throw new OpcacheApiException('opcache_get_configuration() returned false');
        }

        return $config;
    }
}
