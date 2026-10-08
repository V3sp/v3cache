<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Contract;

use V3Cache\OpcacheManager\Model\OpcacheMetrics;

interface MetricsStorageInterface
{
    public function save(string $serverId, OpcacheMetrics $metrics): void;

    public function load(string $serverId): ?OpcacheMetrics;

    /**
     * @return array<string, OpcacheMetrics>
     */
    public function loadAll(): array;

    public function delete(string $serverId): void;
}
