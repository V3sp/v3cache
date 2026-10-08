<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Service\OpcacheService;

final class OptimizeCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheService $service,
    ) {
    }

    public function name(): string
    {
        return 'optimize';
    }

    public function description(): string
    {
        return 'Show OPcache optimization recommendations';
    }

    public function execute(array $args, array $options): int
    {
        $recommendations = $this->service->optimize();

        if ($recommendations === []) {
            echo OutputFormatter::info('No optimization recommendations') . "\n";

            return 0;
        }

        echo OutputFormatter::info('Optimization Recommendations:') . "\n\n";

        foreach ($recommendations as $recommendation) {
            echo \sprintf(
                "%s: %s (current: %s, recommended: %s)\n",
                $recommendation->directive,
                $recommendation->description,
                $recommendation->currentValue,
                $recommendation->recommendedValue,
            );
        }

        return 0;
    }
}
