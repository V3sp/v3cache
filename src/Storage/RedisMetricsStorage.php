<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Storage;

use Predis\Client;
use Predis\Collection\Iterator\Keyspace;
use V3Cache\OpcacheManager\Contract\MetricsStorageInterface;
use V3Cache\OpcacheManager\Exception\StorageException;
use V3Cache\OpcacheManager\Exception\ValidationException;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;

final class RedisMetricsStorage implements MetricsStorageInterface
{
    private const PREFIX = 'opcache:metrics:';
    private const TTL = 3600;
    private const MAX_KEYS = 10000;
    private const MAX_PAYLOAD_BYTES = 1048576;

    private readonly Client $client;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->client = new Client($this->buildOptions($config));
    }

    public function save(string $serverId, OpcacheMetrics $metrics): void
    {
        $key = $this->key($serverId);

        try {
            $payload = json_encode($metrics->toArray(), JSON_THROW_ON_ERROR);
            $this->client->setex($key, self::TTL, $payload);
        } catch (\Throwable $e) {
            throw new StorageException('Failed to save metrics to Redis', 0, $e);
        }
    }

    public function load(string $serverId): ?OpcacheMetrics
    {
        $key = $this->key($serverId);

        try {
            $data = $this->client->get($key);
        } catch (\Throwable $e) {
            throw new StorageException('Failed to load metrics from Redis', 0, $e);
        }

        if (!is_string($data)) {
            return null;
        }

        if (strlen($data) > self::MAX_PAYLOAD_BYTES) {
            throw new StorageException('Redis metrics payload is too large');
        }

        try {
            $decoded = json_decode($data, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new StorageException('Redis metrics payload is invalid', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new StorageException('Redis metrics payload is invalid');
        }

        try {
            return OpcacheMetrics::fromArray($decoded);
        } catch (\Throwable $e) {
            throw new StorageException('Redis metrics payload is invalid', 0, $e);
        }
    }

    /**
     * @return array<string, OpcacheMetrics>
     */
    public function loadAll(): array
    {
        $result = [];

        try {
            $iterator = new Keyspace($this->client, self::PREFIX . '*', 100);

            foreach ($iterator as $key) {
                if (!is_string($key)) {
                    continue;
                }

                $serverId = substr($key, strlen(self::PREFIX));

                if ($this->isValidServerId($serverId) === false) {
                    continue;
                }

                try {
                    $metrics = $this->load($serverId);
                } catch (StorageException) {
                    continue;
                }

                if ($metrics !== null) {
                    $result[$serverId] = $metrics;
                }

                if (count($result) >= self::MAX_KEYS) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            throw new StorageException('Failed to list metrics in Redis', 0, $e);
        }

        return $result;
    }

    public function delete(string $serverId): void
    {
        $key = $this->key($serverId);

        try {
            $this->client->del([$key]);
        } catch (\Throwable $e) {
            throw new StorageException('Failed to delete metrics from Redis', 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function buildOptions(array $config): array
    {
        $host = $config['host'] ?? '127.0.0.1';

        if (
            !is_string($host)
            || $host === ''
            || str_contains($host, '://')
            || preg_match('/[\x00-\x1F\x7F]/', $host) === 1
        ) {
            throw new ValidationException('Invalid Redis host');
        }

        $port = $config['port'] ?? 6379;

        if (!is_int($port) || $port < 1 || $port > 65535) {
            throw new ValidationException('Redis port must be between 1 and 65535');
        }

        $database = $config['db'] ?? 0;

        if (!is_int($database) || $database < 0 || $database > 15) {
            throw new ValidationException('Redis db must be between 0 and 15');
        }

        $tls = $config['tls'] ?? false;

        if (!is_bool($tls)) {
            throw new ValidationException('Redis tls must be a boolean');
        }

        $options = [
            'scheme' => $tls ? 'tls' : 'tcp',
            'host' => $host,
            'port' => $port,
            'password' => $this->resolvePassword($config),
            'database' => $database,
        ];

        foreach (['timeout', 'read_write_timeout'] as $key) {
            if (!array_key_exists($key, $config)) {
                continue;
            }

            $value = $config[$key];

            if (!is_int($value) && !is_float($value)) {
                throw new ValidationException(\sprintf('Redis %s must be a positive number', $key));
            }

            $value = (float) $value;

            if (!is_finite($value) || $value <= 0) {
                throw new ValidationException(\sprintf('Redis %s must be a positive number', $key));
            }

            $options[$key] = $value;
        }

        if (array_key_exists('persistent', $config)) {
            if (!is_bool($config['persistent'])) {
                throw new ValidationException('Redis persistent must be a boolean');
            }

            $options['persistent'] = $config['persistent'];
        }

        if (array_key_exists('ssl', $config)) {
            if (!is_array($config['ssl'])) {
                throw new ValidationException('Redis ssl must be an array of SSL context options');
            }

            $options['ssl'] = $config['ssl'];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolvePassword(array $config): ?string
    {
        $password = $config['password'] ?? null;

        if (array_key_exists('password_env', $config)) {
            $envName = $config['password_env'];

            if (!is_string($envName) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $envName) !== 1) {
                throw new ValidationException('Redis password_env must be a valid environment variable name');
            }

            $envValue = getenv($envName);

            if ($envValue !== false) {
                $password = $envValue;
            }
        }

        if ($password !== null && !is_string($password)) {
            throw new ValidationException('Redis password must be a string or null');
        }

        return $password;
    }

    private function key(string $serverId): string
    {
        if ($this->isValidServerId($serverId) === false) {
            throw new ValidationException('Invalid server id');
        }

        return self::PREFIX . $serverId;
    }

    private function isValidServerId(string $serverId): bool
    {
        return preg_match('/^[A-Za-z0-9._-]{1,64}$/', $serverId) === 1;
    }
}
