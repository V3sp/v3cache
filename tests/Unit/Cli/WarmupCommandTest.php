<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\WarmupCommand;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class WarmupCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/Compiled/');
        $exitCode = $command->execute([], ['file' => $this->createFileList()]);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteOutputsReport(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/Compiled/');
        $command->execute([], ['file' => $this->createFileList()]);
    }

    public function testExecuteWithDir(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/Compiled/');
        $exitCode = $command->execute([], ['dir' => __DIR__ . '/../../../src/Health']);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteWithEmptyFileList(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/No files to warm up/');
        $exitCode = $command->execute([], ['file' => $this->createEmptyFileList()]);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteFallsBackToConfigWarmupPaths(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/Compiled: 1/');
        $exitCode = $command->execute([], ['config' => __DIR__ . '/../../Fixtures/warmup-config.php']);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteWithMissingConfigAndNoOptions(): void
    {
        $command = $this->createCommand();

        $this->expectOutputRegex('/No files to warm up/');
        $exitCode = $command->execute([], ['config' => __DIR__ . '/../../Fixtures/missing-config.php']);

        $this->assertSame(0, $exitCode);
    }

    private function createCommand(): WarmupCommand
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);

        return new WarmupCommand(new OpcacheService($api));
    }

    private function createFileList(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'warmup_');
        file_put_contents($path, __FILE__ . "\n");

        return $path;
    }

    private function createEmptyFileList(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'warmup_');
        file_put_contents($path, '');

        return $path;
    }
}
