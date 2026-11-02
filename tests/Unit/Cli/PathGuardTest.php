<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\PathGuard;

final class PathGuardTest extends TestCase
{
    public function testResolvesFileWithinRoot(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'path_');
        self::assertNotFalse($path);
        file_put_contents($path, '<?php');

        try {
            $this->assertSame(realpath($path), PathGuard::resolveFile($path, [sys_get_temp_dir()]));
        } finally {
            unlink($path);
        }
    }

    public function testRejectsFileOutsideRoot(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'path_');
        self::assertNotFalse($path);
        file_put_contents($path, '<?php');

        try {
            $this->assertNull(PathGuard::resolveFile($path, [__DIR__]));
        } finally {
            unlink($path);
        }
    }

    public function testRejectsSymlink(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'path_');
        self::assertNotFalse($target);
        file_put_contents($target, '<?php');
        $link = $target . '.link.php';
        symlink($target, $link);

        try {
            $this->assertNull(PathGuard::resolveFile($link, [sys_get_temp_dir()]));
        } finally {
            unlink($link);
            unlink($target);
        }
    }

    public function testRejectsDirectoryAsFile(): void
    {
        $this->assertNull(PathGuard::resolveFile(sys_get_temp_dir(), [sys_get_temp_dir()]));
    }

    public function testResolvesDirectoryWithinRoot(): void
    {
        $tempDir = sys_get_temp_dir();

        $this->assertSame(realpath($tempDir), PathGuard::resolveDirectory($tempDir, [$tempDir]));
    }

    public function testNormalizeRootsDropsMissingDirectories(): void
    {
        $this->assertSame([], PathGuard::normalizeRoots(['/nonexistent/root']));
    }
}
