<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Model;

final class ScriptInfo
{
    public function __construct(
        public readonly string $path,
        public readonly float $memoryUsage,
        public readonly int $hits,
        public readonly int $lastUsed,
        public readonly int $timestamp,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['path'], $data['memory_usage'], $data['hits'], $data['last_used'], $data['timestamp'])) {
            throw new \InvalidArgumentException('Missing required keys for ScriptInfo');
        }

        return new self(
            path: (string) $data['path'],
            memoryUsage: (float) $data['memory_usage'],
            hits: (int) $data['hits'],
            lastUsed: (int) $data['last_used'],
            timestamp: (int) $data['timestamp'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'memory_usage' => $this->memoryUsage,
            'hits' => $this->hits,
            'last_used' => $this->lastUsed,
            'timestamp' => $this->timestamp,
        ];
    }
}
