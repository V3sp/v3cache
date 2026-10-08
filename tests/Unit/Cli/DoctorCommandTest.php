<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\DoctorCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;

final class DoctorCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);
        $command = new DoctorCommand($api);

        $this->expectOutputRegex('/All checks passed/');

        $exitCode = $command->execute([], []);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsDiagnostics(): void
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);
        $command = new DoctorCommand($api);

        $this->expectOutputRegex('/PHP version.*opcache/s');

        $command->execute([], []);
    }

    public function testName(): void
    {
        $api = new MockOpcacheApi();
        $command = new DoctorCommand($api);

        $this->assertSame('doctor', $command->name());
    }

    public function testDescription(): void
    {
        $api = new MockOpcacheApi();
        $command = new DoctorCommand($api);

        $this->assertNotEmpty($command->description());
    }
}
