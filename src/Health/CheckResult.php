<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Health;

final class CheckResult
{
    public function __construct(
        public readonly string $name,
        public readonly HealthStatus $status,
        public readonly float|int $actualValue,
        public readonly float|int $threshold,
        public readonly string $message,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['name'], $data['status'], $data['actual_value'], $data['threshold'], $data['message'])) {
            throw new \InvalidArgumentException('Missing required keys for CheckResult');
        }

        return new self(
            name: (string) $data['name'],
            status: HealthStatus::from($data['status']),
            actualValue: is_int($data['actual_value']) ? $data['actual_value'] : (float) $data['actual_value'],
            threshold: is_int($data['threshold']) ? $data['threshold'] : (float) $data['threshold'],
            message: (string) $data['message'],
        );
    }
}
