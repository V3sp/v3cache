<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Storage;

use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Exception\ValidationException;

final class StorageFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config): MetricsStorageInterface
    {
        $type = $config['type'] ?? 'memory';

        return match ($type) {
            'memory' => new InMemoryMetricsStorage(),
            'redis' => new RedisMetricsStorage($config['redis'] ?? []),
            default => throw new ValidationException(\sprintf('Unknown storage type: %s', $type)),
        };
    }
}
