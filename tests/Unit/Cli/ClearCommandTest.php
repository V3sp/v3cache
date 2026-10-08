<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\ClearCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class ClearCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/cleared/');
        $exitCode = $command->execute([], []);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/cleared/');
        $command->execute([], []);
    }

    private function createCommand(): ClearCommand
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);

        return new ClearCommand(new OpcacheService($api));
    }
}
