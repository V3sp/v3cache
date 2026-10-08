<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Service\OpcacheService;

final class ClearCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheService $service,
    ) {
    }

    public function name(): string
    {
        return 'clear';
    }

    public function description(): string
    {
        return 'Clear OPcache';
    }

    public function execute(array $args, array $options): int
    {
        $this->service->reload();

        echo OutputFormatter::success('OPcache cleared successfully') . "\n";

        return 0;
    }
}
