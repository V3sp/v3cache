<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Storage;

use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;

final class InMemoryMetricsStorage implements MetricsStorageInterface
{
    /** @var array<string, OpcacheMetrics> */
    private array $metrics = [];

    public function save(string $serverId, OpcacheMetrics $metrics): void
    {
        $this->metrics[$serverId] = $metrics;
    }

    public function load(string $serverId): ?OpcacheMetrics
    {
        return $this->metrics[$serverId] ?? null;
    }

    public function loadAll(): array
    {
        return $this->metrics;
    }

    public function delete(string $serverId): void
    {
        unset($this->metrics[$serverId]);
    }
}
