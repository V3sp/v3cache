<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Health;

use V3Cache\OpcacheManager\Exception\ValidationException;

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
            minHitRate: self::float($config, 'min_hit_rate', 80.0, 0.0, 100.0),
            maxWastedPercent: self::float($config, 'max_wasted_percent', 20.0, 0.0, 100.0),
            maxRestarts: self::int($config, 'max_restarts', 0),
            maxMemoryUsagePercent: self::float($config, 'max_memory_usage_percent', 90.0, 0.0, 100.0),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function float(array $config, string $key, float $default, float $min, float $max): float
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        $value = $config[$key];

        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            throw new ValidationException(\sprintf('Health threshold "%s" must be numeric', $key));
        }

        $number = (float) $value;

        if (!is_finite($number) || $number < $min || $number > $max) {
            throw new ValidationException(
                \sprintf('Health threshold "%s" must be between %s and %s', $key, $min, $max),
            );
        }

        return $number;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function int(array $config, string $key, int $default): int
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        $value = $config[$key];

        if (!is_int($value) && !(is_string($value) && preg_match('/^\d+$/', $value) === 1)) {
            throw new ValidationException(\sprintf('Health threshold "%s" must be a non-negative integer', $key));
        }

        $number = (int) $value;

        if ($number < 0) {
            throw new ValidationException(\sprintf('Health threshold "%s" must be a non-negative integer', $key));
        }

        return $number;
    }
}
