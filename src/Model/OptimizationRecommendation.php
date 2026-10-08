<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Model;

use V3Cache\OpcacheManager\Health\HealthStatus;

final class OptimizationRecommendation
{
    public function __construct(
        public readonly string $directive,
        public readonly string|int|bool $currentValue,
        public readonly string|int|bool $recommendedValue,
        public readonly HealthStatus $severity,
        public readonly string $description,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (
            !isset(
                $data['directive'],
                $data['current_value'],
                $data['recommended_value'],
                $data['severity'],
                $data['description']
            )
        ) {
            throw new \InvalidArgumentException('Missing required keys for OptimizationRecommendation');
        }

        return new self(
            directive: (string) $data['directive'],
            currentValue: $data['current_value'],
            recommendedValue: $data['recommended_value'],
            severity: HealthStatus::from($data['severity']),
            description: (string) $data['description'],
        );
    }
}
