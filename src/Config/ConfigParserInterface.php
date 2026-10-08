<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

interface ConfigParserInterface
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $path): array;

    public function supports(string $path): bool;
}
