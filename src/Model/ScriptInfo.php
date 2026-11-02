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
        foreach (['path', 'memory_usage', 'hits', 'last_used', 'timestamp'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException(\sprintf('Missing required key "%s" for ScriptInfo', $key));
            }
        }

        $path = $data['path'];

        if (!is_string($path) || $path === '' || strlen($path) > 4096 || str_contains($path, "\0")) {
            throw new \InvalidArgumentException('Invalid ScriptInfo path');
        }

        return new self(
            path: $path,
            memoryUsage: self::float($data, 'memory_usage'),
            hits: self::int($data, 'hits'),
            lastUsed: self::int($data, 'last_used'),
            timestamp: self::int($data, 'timestamp'),
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

    /**
     * @param array<string, mixed> $data
     */
    private static function float(array $data, string $key): float
    {
        $value = $data[$key];

        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            throw new \InvalidArgumentException(\sprintf('Invalid ScriptInfo value for "%s"', $key));
        }

        $number = (float) $value;

        if (!is_finite($number) || $number < 0.0) {
            throw new \InvalidArgumentException(\sprintf('Invalid ScriptInfo value for "%s"', $key));
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
            throw new \InvalidArgumentException(\sprintf('Invalid ScriptInfo value for "%s"', $key));
        }

        if ($number < 0) {
            throw new \InvalidArgumentException(\sprintf('Invalid ScriptInfo value for "%s"', $key));
        }

        return $number;
    }
}
