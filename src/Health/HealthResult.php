<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Health;

final class HealthResult
{
    /**
     * @param array<string, CheckResult> $checks
     */
    public function __construct(
        public readonly HealthStatus $status,
        public readonly array $checks,
        public readonly string $summary,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['status'], $data['checks'], $data['summary'])) {
            throw new \InvalidArgumentException('Missing required keys for HealthResult');
        }

        $checks = [];
        foreach ($data['checks'] as $name => $checkData) {
            $checks[$name] = CheckResult::fromArray($checkData);
        }

        return new self(
            status: HealthStatus::from($data['status']),
            checks: $checks,
            summary: (string) $data['summary'],
        );
    }
}
