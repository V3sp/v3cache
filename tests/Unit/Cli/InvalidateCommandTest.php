<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\InvalidateCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class InvalidateCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/invalidated/');
        $exitCode = $command->execute(['/path/to/file.php'], []);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/invalidated/');
        $command->execute(['/path/to/file.php'], []);
    }

    public function testExecuteReturnsNonZeroWhenPathMissing(): void
    {
        $command = $this->createCommand();

        $exitCode = $command->execute([], []);

        $this->assertNotSame(0, $exitCode);
    }

    private function createCommand(): InvalidateCommand
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);

        return new InvalidateCommand(new OpcacheService($api));
    }
}
