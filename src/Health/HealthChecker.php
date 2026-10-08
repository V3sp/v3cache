<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Health;

use V3Cache\OpcacheManager\Model\OpcacheMetrics;

final class HealthChecker
{
    public function __construct(
        private readonly HealthThresholds $thresholds,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(HealthThresholds::fromConfig($config));
    }

    public function check(OpcacheMetrics $metrics): HealthResult
    {
        $checks = [];

        $checks['hitRate'] = $this->checkHitRate($metrics);
        $checks['memoryWasted'] = $this->checkMemoryWasted($metrics);
        $checks['restarts'] = $this->checkRestarts($metrics);
        $checks['memoryUsage'] = $this->checkMemoryUsage($metrics);

        $overallStatus = $this->determineOverallStatus($checks);

        return new HealthResult(
            status: $overallStatus,
            checks: $checks,
            summary: $this->buildSummary($overallStatus, $checks),
        );
    }

    private function checkHitRate(OpcacheMetrics $metrics): CheckResult
    {
        $threshold = $this->thresholds->minHitRate;
        $actual = $metrics->hitRate;

        if ($actual < $threshold) {
            return new CheckResult(
                name: 'hitRate',
                status: HealthStatus::WARN,
                actualValue: $actual,
                threshold: $threshold,
                message: \sprintf('Hit rate %.1f%% below threshold %.1f%%', $actual, $threshold),
            );
        }

        return new CheckResult(
            name: 'hitRate',
            status: HealthStatus::OK,
            actualValue: $actual,
            threshold: $threshold,
            message: \sprintf('Hit rate %.1f%% above threshold %.1f%%', $actual, $threshold),
        );
    }

    private function checkMemoryWasted(OpcacheMetrics $metrics): CheckResult
    {
        $threshold = $this->thresholds->maxWastedPercent;
        $actual = $metrics->memoryWasted;

        if ($actual > $threshold) {
            return new CheckResult(
                name: 'memoryWasted',
                status: HealthStatus::WARN,
                actualValue: $actual,
                threshold: $threshold,
                message: \sprintf('Wasted memory %.1f%% above threshold %.1f%%', $actual, $threshold),
            );
        }

        return new CheckResult(
            name: 'memoryWasted',
            status: HealthStatus::OK,
            actualValue: $actual,
            threshold: $threshold,
            message: \sprintf('Wasted memory %.1f%% below threshold %.1f%%', $actual, $threshold),
        );
    }

    private function checkRestarts(OpcacheMetrics $metrics): CheckResult
    {
        $threshold = $this->thresholds->maxRestarts;
        $actual = $metrics->restarts;

        if ($actual > $threshold) {
            return new CheckResult(
                name: 'restarts',
                status: HealthStatus::CRIT,
                actualValue: $actual,
                threshold: $threshold,
                message: \sprintf('Restarts %d above threshold %d', $actual, $threshold),
            );
        }

        return new CheckResult(
            name: 'restarts',
            status: HealthStatus::OK,
            actualValue: $actual,
            threshold: $threshold,
            message: \sprintf('Restarts %d within threshold %d', $actual, $threshold),
        );
    }

    private function checkMemoryUsage(OpcacheMetrics $metrics): CheckResult
    {
        $threshold = $this->thresholds->maxMemoryUsagePercent;
        $actual = $metrics->memoryUsage;

        if ($actual > $threshold) {
            return new CheckResult(
                name: 'memoryUsage',
                status: HealthStatus::WARN,
                actualValue: $actual,
                threshold: $threshold,
                message: \sprintf('Memory usage %.1f%% above threshold %.1f%%', $actual, $threshold),
            );
        }

        return new CheckResult(
            name: 'memoryUsage',
            status: HealthStatus::OK,
            actualValue: $actual,
            threshold: $threshold,
            message: \sprintf('Memory usage %.1f%% below threshold %.1f%%', $actual, $threshold),
        );
    }

    /**
     * @param array<string, CheckResult> $checks
     */
    private function determineOverallStatus(array $checks): HealthStatus
    {
        $worst = HealthStatus::OK;

        foreach ($checks as $check) {
            if ($this->isWorse($check->status, $worst)) {
                $worst = $check->status;
            }
        }

        return $worst;
    }

    private function isWorse(HealthStatus $a, HealthStatus $b): bool
    {
        $order = [HealthStatus::OK->value => 0, HealthStatus::WARN->value => 1, HealthStatus::CRIT->value => 2];

        return $order[$a->value] > $order[$b->value];
    }

    /**
     * @param array<string, CheckResult> $checks
     */
    private function buildSummary(HealthStatus $status, array $checks): string
    {
        $failed = [];
        foreach ($checks as $name => $check) {
            if ($check->status !== HealthStatus::OK) {
                $failed[] = $check->message;
            }
        }

        if ($failed === []) {
            return 'All checks passed';
        }

        return \sprintf('%s: %s', $status->value, implode('; ', $failed));
    }
}
