<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

use V3Cache\OpcacheManager\Exception\ValidationException;

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
        if (!file_exists($path)) {
            throw new ValidationException(\sprintf('Config file not found: %s', $path));
        }

        $parser = $this->registry->for($path);
        $config = $parser->parse($path);

        $this->validator->validate($config);

        return $config;
    }
}
