<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Health\HealthChecker;
use V3Cache\OpcacheManager\Health\HealthStatus;
use V3Cache\OpcacheManager\Health\HealthThresholds;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class HealthCheckCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheService $service,
        private readonly ?HealthStatus $forcedStatus = null,
    ) {
    }

    public function name(): string
    {
        return 'health-check';
    }

    public function description(): string
    {
        return 'Check OPcache health';
    }

    public function execute(array $args, array $options): int
    {
        $format = $options['format'] ?? $options['f'] ?? 'table';

        $checker = new HealthChecker(new HealthThresholds());
        $result = $checker->check($this->service->getMetrics());

        $status = $this->forcedStatus ?? $result->status;

        if ($format === 'nagios') {
            echo \sprintf('%s - %s', strtoupper($status->value), $result->summary) . "\n";
        } elseif ($format === 'json') {
            $json = json_encode([
                'status' => $status->value,
                'summary' => $result->summary,
                'checks' => array_map(fn ($check) => [
                    'name' => $check->name,
                    'status' => $check->status->value,
                    'actual_value' => $check->actualValue,
                    'threshold' => $check->threshold,
                    'message' => $check->message,
                ], $result->checks),
            ], JSON_THROW_ON_ERROR);
            echo $json . "\n";
        } else {
            echo OutputFormatter::info(\sprintf('Status: %s', strtoupper($status->value))) . "\n\n";
            foreach ($result->checks as $check) {
                echo \sprintf(
                    "%s: %s (actual: %s, threshold: %s)\n",
                    $check->name,
                    $check->status->value,
                    $check->actualValue,
                    $check->threshold,
                );
            }
        }

        return match ($status) {
            HealthStatus::OK => 0,
            HealthStatus::WARN => 1,
            HealthStatus::CRIT => 2,
        };
    }
}
