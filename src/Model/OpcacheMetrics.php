<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Model;

final class OpcacheMetrics
{
    private const MAX_SCRIPTS = 100000;

    /**
     * @param array<int, ScriptInfo> $scripts
     */
    public function __construct(
        public readonly float $memoryUsage,
        public readonly float $memoryFree,
        public readonly float $memoryWasted,
        public readonly float $hitRate,
        public readonly int $cachedScripts,
        public readonly int $cachedKeys,
        public readonly int $maxCachedKeys,
        public readonly int $restarts,
        public readonly int $startTime,
        public readonly ?int $lastRestartTime,
        public readonly array $scripts,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $required = [
            'memory_usage',
            'memory_free',
            'memory_wasted',
            'hit_rate',
            'cached_scripts',
            'cached_keys',
            'max_cached_keys',
            'restarts',
            'start_time',
            'last_restart_time',
            'scripts',
        ];

        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException(\sprintf('Missing required key "%s" for OpcacheMetrics', $key));
            }
        }

        if (!is_array($data['scripts'])) {
            throw new \InvalidArgumentException('OpcacheMetrics scripts must be an array');
        }

        if (count($data['scripts']) > self::MAX_SCRIPTS) {
            throw new \InvalidArgumentException('OpcacheMetrics contains too many scripts');
        }

        $scripts = [];

        foreach ($data['scripts'] as $scriptData) {
            if (!is_array($scriptData)) {
                throw new \InvalidArgumentException('OpcacheMetrics script entries must be arrays');
            }

            $scripts[] = ScriptInfo::fromArray($scriptData);
        }

        return new self(
            memoryUsage: self::float($data, 'memory_usage'),
            memoryFree: self::float($data, 'memory_free'),
            memoryWasted: self::float($data, 'memory_wasted'),
            hitRate: self::float($data, 'hit_rate', 0.0, 100.0),
            cachedScripts: self::int($data, 'cached_scripts'),
            cachedKeys: self::int($data, 'cached_keys'),
            maxCachedKeys: self::int($data, 'max_cached_keys'),
            restarts: self::int($data, 'restarts'),
            startTime: self::int($data, 'start_time'),
            lastRestartTime: $data['last_restart_time'] === null
                ? null
                : self::int($data, 'last_restart_time'),
            scripts: $scripts,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'memory_usage' => $this->memoryUsage,
            'memory_free' => $this->memoryFree,
            'memory_wasted' => $this->memoryWasted,
            'hit_rate' => $this->hitRate,
            'cached_scripts' => $this->cachedScripts,
            'cached_keys' => $this->cachedKeys,
            'max_cached_keys' => $this->maxCachedKeys,
            'restarts' => $this->restarts,
            'start_time' => $this->startTime,
            'last_restart_time' => $this->lastRestartTime,
            'scripts' => array_map(static fn (ScriptInfo $script): array => $script->toArray(), $this->scripts),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function float(array $data, string $key, float $min = 0.0, ?float $max = null): float
    {
        $value = $data[$key];

        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            throw new \InvalidArgumentException(\sprintf('Invalid OpcacheMetrics value for "%s"', $key));
        }

        $number = (float) $value;

        if (!is_finite($number) || $number < $min || ($max !== null && $number > $max)) {
            throw new \InvalidArgumentException(\sprintf('Invalid OpcacheMetrics value for "%s"', $key));
        }

        return $number;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function int(array $data, string $key): int
    {
        $value = $data[$key];

        if (is_int($value)) {
            $number = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $number = (int) $value;
        } else {
            throw new \InvalidArgumentException(\sprintf('Invalid OpcacheMetrics value for "%s"', $key));
        }

        if ($number < 0) {
            throw new \InvalidArgumentException(\sprintf('Invalid OpcacheMetrics value for "%s"', $key));
        }

        return $number;
    }
}
