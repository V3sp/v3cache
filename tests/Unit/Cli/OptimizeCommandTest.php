<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\OptimizeCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class OptimizeCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/recommendation/');
        $exitCode = $command->execute([], []);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsRecommendations(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/recommendation/');
        $command->execute([], []);
    }

    private function createCommand(): OptimizeCommand
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);

        return new OptimizeCommand(new OpcacheService($api));
    }
}
