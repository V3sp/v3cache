<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\HealthCheckCommand;
use V3Cache\OpcacheManager\Health\HealthStatus;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class HealthCheckCommandTest extends TestCase
{
    public function testExecuteReturnsZeroForOkStatus(): void
    {
        $command = $this->createCommand(HealthStatus::OK);

        $this->expectOutputRegex('/Status: OK/');
        $exitCode = $command->execute([], []);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteReturnsOneForWarnStatus(): void
    {
        $command = $this->createCommand(HealthStatus::WARN);

        $this->expectOutputRegex('/Status: WARN/');
        $exitCode = $command->execute([], []);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteReturnsTwoForCritStatus(): void
    {
        $command = $this->createCommand(HealthStatus::CRIT);

        $this->expectOutputRegex('/Status: CRIT/');
        $exitCode = $command->execute([], []);

        $this->assertSame(2, $exitCode);
    }

    public function testExecuteWithNagiosFormat(): void
    {
        $command = $this->createCommand(HealthStatus::OK);

        $this->expectOutputRegex('/OK/');
        $command->execute([], ['format' => 'nagios']);
    }

    public function testExecuteWithJsonFormat(): void
    {
        $command = $this->createCommand(HealthStatus::OK);

        $this->expectOutputRegex('/^\{.*\}$/s');
        $command->execute([], ['format' => 'json']);
    }

    private function createCommand(?HealthStatus $status = null): HealthCheckCommand
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);
        $api->setStatus([
            'opcache_enabled' => true,
            'cache_full' => false,
            'memory_usage' => [
                'used_memory' => 67108864,
                'free_memory' => 37748736,
                'wasted_memory' => 0,
                'current_wasted_percentage' => 0.0,
            ],
            'opcache_statistics' => [
                'num_cached_scripts' => 10,
                'num_cached_keys' => 15,
                'max_cached_keys' => 10000,
                'hits' => 100,
                'misses' => 5,
                'blacklist_misses' => 0,
                'blacklist_miss_ratio' => 0.0,
                'opcache_hit_rate' => 95.2,
                'start_time' => 1234567890,
                'last_restart_time' => null,
                'oom_restarts' => 0,
                'hash_restarts' => 0,
                'manual_restarts' => 0,
            ],
            'scripts' => [],
        ]);

        $service = new OpcacheService($api);

        return new HealthCheckCommand($service, $status);
    }
}
