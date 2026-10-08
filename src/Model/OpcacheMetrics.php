<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Model;

final class OpcacheMetrics
{
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

        $scripts = [];
        foreach ($data['scripts'] as $scriptData) {
            $scripts[] = ScriptInfo::fromArray($scriptData);
        }

        return new self(
            memoryUsage: (float) $data['memory_usage'],
            memoryFree: (float) $data['memory_free'],
            memoryWasted: (float) $data['memory_wasted'],
            hitRate: (float) $data['hit_rate'],
            cachedScripts: (int) $data['cached_scripts'],
            cachedKeys: (int) $data['cached_keys'],
            maxCachedKeys: (int) $data['max_cached_keys'],
            restarts: (int) $data['restarts'],
            startTime: (int) $data['start_time'],
            lastRestartTime: $data['last_restart_time'] !== null ? (int) $data['last_restart_time'] : null,
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
}
