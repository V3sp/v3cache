<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Exception\OpcacheNotEnabledException;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class StatusCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheService $service,
    ) {
    }

    public function name(): string
    {
        return 'status';
    }

    public function description(): string
    {
        return 'Show OPcache status';
    }

    public function execute(array $args, array $options): int
    {
        $format = $options['format'] ?? $options['f'] ?? 'table';

        try {
            $metrics = $this->service->getMetrics();
        } catch (OpcacheNotEnabledException $e) {
            fwrite(STDERR, OutputFormatter::error($e->getMessage()) . "\n");

            return 1;
        }

        if ($format === 'json') {
            $json = json_encode([
                'memory_usage' => $metrics->memoryUsage,
                'memory_free' => $metrics->memoryFree,
                'memory_wasted' => $metrics->memoryWasted,
                'hit_rate' => $metrics->hitRate,
                'cached_scripts' => $metrics->cachedScripts,
                'cached_keys' => $metrics->cachedKeys,
                'max_cached_keys' => $metrics->maxCachedKeys,
                'restarts' => $metrics->restarts,
                'start_time' => $metrics->startTime,
                'last_restart_time' => $metrics->lastRestartTime,
            ], JSON_THROW_ON_ERROR);
            echo $json . "\n";
        } else {
            echo OutputFormatter::info('OPcache Status') . "\n\n";
            echo \sprintf("Memory Usage:    %.1f MB\n", $metrics->memoryUsage);
            echo \sprintf("Memory Free:     %.1f MB\n", $metrics->memoryFree);
            echo \sprintf("Memory Wasted:   %.1f MB\n", $metrics->memoryWasted);
            echo \sprintf("Hit Rate:        %.1f%%\n", $metrics->hitRate);
            echo \sprintf("Cached Scripts:  %d\n", $metrics->cachedScripts);
            echo \sprintf("Cached Keys:     %d\n", $metrics->cachedKeys);
            echo \sprintf("Max Cached Keys: %d\n", $metrics->maxCachedKeys);
            echo \sprintf("Restarts:        %d\n", $metrics->restarts);
        }

        return 0;
    }
}
