<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

interface CommandInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @param array<int, string> $args
     * @param array<string, string> $options
     */
    public function execute(array $args, array $options): int;
}
