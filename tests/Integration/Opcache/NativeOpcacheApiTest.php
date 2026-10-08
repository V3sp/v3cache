<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Integration\Opcache;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Opcache\NativeOpcacheApi;

final class NativeOpcacheApiTest extends TestCase
{
    private NativeOpcacheApi $api;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->api = new NativeOpcacheApi();

        if (!$this->api->isEnabled()) {
            $this->markTestSkipped('OPcache is not enabled in CLI');
        }

        $this->tempFile = tempnam(sys_get_temp_dir(), 'opcache_test_');
        $functionName = 'opcache_test_' . uniqid();
        $content = "<?php\n\ndeclare(strict_types=1);\n\n"
            . "function {$functionName}(): string\n{\n    return 'test';\n}\n";
        file_put_contents($this->tempFile, $content);
    }

    protected function tearDown(): void
    {
        if (isset($this->tempFile) && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testIsEnabledReturnsTrue(): void
    {
        $this->assertTrue($this->api->isEnabled());
    }

    public function testGetStatusReturnsArray(): void
    {
        $status = $this->api->getStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('memory_usage', $status);
        $this->assertArrayHasKey('opcache_statistics', $status);
    }

    public function testGetStatusWithScriptsReturnsScripts(): void
    {
        $status = $this->api->getStatus(true);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('scripts', $status);
    }

    public function testGetConfigurationReturnsArray(): void
    {
        $config = $this->api->getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('directives', $config);
    }

    public function testResetReturnsTrue(): void
    {
        $this->assertTrue($this->api->reset());
    }

    public function testCompileFileReturnsTrue(): void
    {
        $this->assertTrue($this->api->compileFile($this->tempFile));
    }

    public function testInvalidateReturnsTrue(): void
    {
        $this->api->compileFile($this->tempFile);

        $this->assertTrue($this->api->invalidate($this->tempFile));
    }
}
