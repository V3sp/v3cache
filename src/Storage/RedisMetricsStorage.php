<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Storage;

use Predis\Client;
use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;

final class RedisMetricsStorage implements MetricsStorageInterface
{
    private const PREFIX = 'opcache:metrics:';
    private const TTL = 3600;

    private readonly Client $client;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => (int) ($config['port'] ?? 6379),
            'password' => $config['password'] ?? null,
            'database' => (int) ($config['db'] ?? 0),
        ]);
    }

    public function save(string $serverId, OpcacheMetrics $metrics): void
    {
        $this->client->setex(
            self::PREFIX . $serverId,
            self::TTL,
            json_encode($metrics->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    public function load(string $serverId): ?OpcacheMetrics
    {
        $data = $this->client->get(self::PREFIX . $serverId);

        if (!is_string($data)) {
            return null;
        }

        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            return null;
        }

        return OpcacheMetrics::fromArray($decoded);
    }

    /**
     * @return array<string, OpcacheMetrics>
     */
    public function loadAll(): array
    {
        $keys = $this->client->keys(self::PREFIX . '*');

        if (!is_array($keys)) {
            return [];
        }

        $result = [];

        foreach ($keys as $key) {
            $serverId = substr((string) $key, strlen(self::PREFIX));
            $metrics = $this->load($serverId);

            if ($metrics !== null) {
                $result[$serverId] = $metrics;
            }
        }

        return $result;
    }

    public function delete(string $serverId): void
    {
        $this->client->del([self::PREFIX . $serverId]);
    }
}
