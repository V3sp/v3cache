<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class ConfigLoader
{
    public function __construct(
        private readonly ConfigParserRegistry $registry,
        private readonly ConfigValidator $validator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $path): array
    {
        $resolvedPath = ConfigPathGuard::resolve($path);

        $parser = $this->registry->for($resolvedPath);
        $config = $parser->parse($resolvedPath);

        $this->validator->validate($config);

        return $config;
    }
}
