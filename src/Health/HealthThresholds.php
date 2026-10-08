<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Health;

final class HealthThresholds
{
    public function __construct(
        public readonly float $minHitRate = 80.0,
        public readonly float $maxWastedPercent = 20.0,
        public readonly int $maxRestarts = 0,
        public readonly float $maxMemoryUsagePercent = 90.0,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            minHitRate: isset($config['min_hit_rate'])
                ? (float) $config['min_hit_rate']
                : 80.0,
            maxWastedPercent: isset($config['max_wasted_percent'])
                ? (float) $config['max_wasted_percent']
                : 20.0,
            maxRestarts: isset($config['max_restarts'])
                ? (int) $config['max_restarts']
                : 0,
            maxMemoryUsagePercent: isset($config['max_memory_usage_percent'])
                ? (float) $config['max_memory_usage_percent']
                : 90.0,
        );
    }
}
