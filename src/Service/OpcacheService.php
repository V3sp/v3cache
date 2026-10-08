<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Service;

use V3Cache\OpcacheManager\Exception\OpcacheNotEnabledException;
use V3Cache\OpcacheManager\Health\HealthStatus;
use V3Cache\OpcacheManager\Model\OpcacheMetrics;
use V3Cache\OpcacheManager\Model\OptimizationRecommendation;
use V3Cache\OpcacheManager\Model\ScriptInfo;
use V3Cache\OpcacheManager\Opcache\OpcacheApiInterface;

final class OpcacheService
{
    public function __construct(
        private readonly OpcacheApiInterface $api,
    ) {
    }

    public function getMetrics(): OpcacheMetrics
    {
        if (!$this->api->isEnabled()) {
            throw new OpcacheNotEnabledException('OPcache is not enabled');
        }

        $status = $this->api->getStatus(true);

        if ($status === null) {
            throw new OpcacheNotEnabledException('OPcache status is not available');
        }

        return $this->mapStatusToMetrics($status);
    }

    /**
     * @return array<int, ScriptInfo>
     */
    public function getScripts(): array
    {
        if (!$this->api->isEnabled()) {
            throw new OpcacheNotEnabledException('OPcache is not enabled');
        }

        $status = $this->api->getStatus(true);

        if ($status === null) {
            throw new OpcacheNotEnabledException('OPcache status is not available');
        }

        return $this->mapStatusToScripts($status);
    }

    public function reload(): bool
    {
        return $this->api->reset();
    }

    public function invalidate(string $path): bool
    {
        return $this->api->invalidate($path);
    }

    public function compileFile(string $path): bool
    {
        return $this->api->compileFile($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->api->getConfiguration();
    }

    /**
     * @return array<int, OptimizationRecommendation>
     */
    public function optimize(): array
    {
        $config = $this->api->getConfiguration();
        $directives = $config['directives'] ?? [];

        $recommendations = [];

        $memoryConsumption = $directives['opcache.memory_consumption'] ?? null;
        if ($memoryConsumption !== null && (int) $memoryConsumption < 128) {
            $recommendations[] = new OptimizationRecommendation(
                directive: 'opcache.memory_consumption',
                currentValue: $memoryConsumption,
                recommendedValue: '128',
                severity: HealthStatus::WARN,
                description: 'Consider increasing opcache.memory_consumption to at least 128MB',
            );
        }

        $maxAcceleratedFiles = $directives['opcache.max_accelerated_files'] ?? null;
        if ($maxAcceleratedFiles !== null && (int) $maxAcceleratedFiles < 10000) {
            $recommendations[] = new OptimizationRecommendation(
                directive: 'opcache.max_accelerated_files',
                currentValue: $maxAcceleratedFiles,
                recommendedValue: '10000',
                severity: HealthStatus::WARN,
                description: 'Consider increasing opcache.max_accelerated_files to at least 10000',
            );
        }

        $validateTimestamps = $directives['opcache.validate_timestamps'] ?? null;
        if ($validateTimestamps !== null && (int) $validateTimestamps === 1) {
            $recommendations[] = new OptimizationRecommendation(
                directive: 'opcache.validate_timestamps',
                currentValue: $validateTimestamps,
                recommendedValue: '0',
                severity: HealthStatus::OK,
                description: 'Consider disabling opcache.validate_timestamps in production',
            );
        }

        $revalidateFreq = $directives['opcache.revalidate_freq'] ?? null;
        if ($revalidateFreq !== null && (int) $revalidateFreq > 0) {
            $recommendations[] = new OptimizationRecommendation(
                directive: 'opcache.revalidate_freq',
                currentValue: $revalidateFreq,
                recommendedValue: '0',
                severity: HealthStatus::OK,
                description: 'Consider setting opcache.revalidate_freq to 0 in production',
            );
        }

        return $recommendations;
    }

    /**
     * @param array<string, mixed> $status
     */
    private function mapStatusToMetrics(array $status): OpcacheMetrics
    {
        $memoryUsage = $status['memory_usage'] ?? [];
        $statistics = $status['opcache_statistics'] ?? [];

        $usedMemory = (int) ($memoryUsage['used_memory'] ?? 0);
        $freeMemory = (int) ($memoryUsage['free_memory'] ?? 0);
        $wastedMemory = (int) ($memoryUsage['wasted_memory'] ?? 0);

        $oomRestarts = (int) ($statistics['oom_restarts'] ?? 0);
        $hashRestarts = (int) ($statistics['hash_restarts'] ?? 0);
        $manualRestarts = (int) ($statistics['manual_restarts'] ?? 0);

        $scripts = [];
        foreach ($status['scripts'] ?? [] as $scriptData) {
            $scripts[] = new ScriptInfo(
                path: (string) ($scriptData['full_path'] ?? ''),
                memoryUsage: (int) ($scriptData['memory_consumption'] ?? 0) / 1048576,
                hits: (int) ($scriptData['hits'] ?? 0),
                lastUsed: (int) ($scriptData['last_used'] ?? 0),
                timestamp: (int) ($scriptData['timestamp'] ?? 0),
            );
        }

        return new OpcacheMetrics(
            memoryUsage: $usedMemory / 1048576,
            memoryFree: $freeMemory / 1048576,
            memoryWasted: $wastedMemory / 1048576,
            hitRate: (float) ($statistics['opcache_hit_rate'] ?? 0.0),
            cachedScripts: (int) ($statistics['num_cached_scripts'] ?? 0),
            cachedKeys: (int) ($statistics['num_cached_keys'] ?? 0),
            maxCachedKeys: (int) ($statistics['max_cached_keys'] ?? 0),
            restarts: $oomRestarts + $hashRestarts + $manualRestarts,
            startTime: (int) ($statistics['start_time'] ?? 0),
            lastRestartTime: isset($statistics['last_restart_time']) ? (int) $statistics['last_restart_time'] : null,
            scripts: $scripts,
        );
    }

    /**
     * @param array<string, mixed> $status
     * @return array<int, ScriptInfo>
     */
    private function mapStatusToScripts(array $status): array
    {
        $scripts = [];
        foreach ($status['scripts'] ?? [] as $scriptData) {
            $scripts[] = new ScriptInfo(
                path: (string) ($scriptData['full_path'] ?? ''),
                memoryUsage: (int) ($scriptData['memory_consumption'] ?? 0) / 1048576,
                hits: (int) ($scriptData['hits'] ?? 0),
                lastUsed: (int) ($scriptData['last_used'] ?? 0),
                timestamp: (int) ($scriptData['timestamp'] ?? 0),
            );
        }

        return $scripts;
    }
}
