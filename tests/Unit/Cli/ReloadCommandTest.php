<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\ReloadCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class ReloadCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/reloaded/');
        $exitCode = $command->execute([], []);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/reloaded/');
        $command->execute([], []);
    }

    private function createCommand(): ReloadCommand
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);

        return new ReloadCommand(new OpcacheService($api));
    }
}
