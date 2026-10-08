<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Opcache;

interface OpcacheApiInterface
{
    public function isEnabled(): bool;

    /**
     * @return array<string, mixed>|null
     */
    public function getStatus(bool $withScripts = false): ?array;

    public function reset(): bool;

    public function invalidate(string $path, bool $force = true): bool;

    public function compileFile(string $path): bool;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;
}
