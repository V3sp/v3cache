<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

use V3Cache\OpcacheManager\Exception\ValidationException;

final class ConfigParserRegistry
{
    /** @var array<int, ConfigParserInterface> */
    private array $parsers;

    /**
     * @param array<int, ConfigParserInterface> $parsers
     */
    public function __construct(array $parsers)
    {
        $this->parsers = $parsers;
    }

    public function for(string $path): ConfigParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($path)) {
                return $parser;
            }
        }

        throw new ValidationException(\sprintf('No parser found for config file: %s', $path));
    }
}
