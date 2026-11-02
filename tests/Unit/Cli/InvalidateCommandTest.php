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
        $path = $this->createTempFile();

        try {
            $this->expectOutputRegex('/invalidated/');
            $exitCode = $command->execute([$path], ['root' => sys_get_temp_dir()]);

            $this->assertSame(0, $exitCode);
        } finally {
            unlink($path);
        }
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        $command = $this->createCommand();
        $path = $this->createTempFile();

        try {
            $this->expectOutputRegex('/invalidated/');
            $command->execute([$path], ['root' => sys_get_temp_dir()]);
        } finally {
            unlink($path);
        }
    }

    public function testExecuteReturnsNonZeroWhenPathMissing(): void
    {
        $command = $this->createCommand();

        $exitCode = $command->execute([], []);

        $this->assertNotSame(0, $exitCode);
    }

    public function testExecuteReturnsNonZeroWhenFileDoesNotExist(): void
    {
        $command = $this->createCommand();

        $exitCode = $command->execute(['/path/to/missing.php'], ['root' => sys_get_temp_dir()]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteRejectsPathOutsideAllowedRoots(): void
    {
        $command = $this->createCommand();
        $path = $this->createTempFile();

        try {
            $exitCode = $command->execute([$path], ['root' => __DIR__]);

            $this->assertSame(1, $exitCode);
        } finally {
            unlink($path);
        }
    }

    private function createCommand(): InvalidateCommand
    {
        $api = new MockOpcacheApi();
        $api->setEnabled(true);

        return new InvalidateCommand(new OpcacheService($api));
    }

    private function createTempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'invalidate_');
        self::assertNotFalse($path);

        return $path;
    }
}
