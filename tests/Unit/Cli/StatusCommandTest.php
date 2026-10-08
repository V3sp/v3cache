<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\StatusCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class StatusCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/OPcache Status/');

        $exitCode = $command->execute([], []);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsMetrics(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/Memory Usage.*Hit Rate.*Cached Scripts/s');

        $command->execute([], []);
    }

    public function testExecuteWithJsonFormat(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/"memory_usage"/');

        $command->execute([], ['format' => 'json']);
    }

    public function testExecuteThrowsWhenOpcacheNotEnabled(): void
    {
        $api = new MockOpcacheApi();
        $service = new OpcacheService($api);
        $command = new StatusCommand($service);

        $exitCode = $command->execute([], []);

        $this->assertNotSame(0, $exitCode);
    }

    private function createCommand(): StatusCommand
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

        return new StatusCommand(new OpcacheService($api));
    }
}
