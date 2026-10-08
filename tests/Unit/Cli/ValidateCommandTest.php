<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\ValidateCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;

final class ValidateCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);
        $command = new ValidateCommand($api);

        $ok = "\033[32mOK\033[0m";
        $expected = \sprintf(
            "%s\n\n"
            . "  Config parsing                 %s (valid)\n"
            . "  Storage configuration          %s (valid)\n"
            . "  Multi-server                   %s (not configured)\n"
            . "  OPcache enabled                %s (yes)\n\n%s\n",
            "\033[34mConfiguration Validation\033[0m",
            $ok,
            $ok,
            $ok,
            $ok,
            "\033[32mAll checks passed\033[0m",
        );

        $this->expectOutputString($expected);

        $exitCode = $command->execute([], ['config' => __DIR__ . '/../../Fixtures/config.php']);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsValidation(): void
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);
        $command = new ValidateCommand($api);

        $this->expectOutputRegex('/Config parsing/');

        $command->execute([], ['config' => __DIR__ . '/../../Fixtures/config.php']);
    }

    public function testExecuteReturnsNonZeroWhenConfigMissing(): void
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);
        $command = new ValidateCommand($api);

        $exitCode = $command->execute([], ['config' => '/nonexistent/config.php']);

        $this->assertNotSame(0, $exitCode);
    }

    public function testName(): void
    {
        $api = new MockOpcacheApi();
        $command = new ValidateCommand($api);

        $this->assertSame('validate', $command->name());
    }

    public function testDescription(): void
    {
        $api = new MockOpcacheApi();
        $command = new ValidateCommand($api);

        $this->assertNotEmpty($command->description());
    }
}
