<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Opcache;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Opcache\MockOpcacheApi;
use V3Cache\OpcacheManager\Opcache\OpcacheApiInterface;

final class MockOpcacheApiTest extends TestCase
{
    private MockOpcacheApi $api;

    protected function setUp(): void
    {
        $this->api = new MockOpcacheApi();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(OpcacheApiInterface::class, $this->api);
    }

    public function testIsEnabledReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->api->isEnabled());
    }

    public function testSetEnabledChangesIsEnabled(): void
    {
        $this->api->setEnabled(true);

        $this->assertTrue($this->api->isEnabled());
    }

    public function testGetStatusReturnsNullByDefault(): void
    {
        $this->assertNull($this->api->getStatus());
    }

    public function testSetStatusChangesGetStatus(): void
    {
        $status = ['memory_usage' => ['used_memory' => 65536]];
        $this->api->setStatus($status);

        $this->assertSame($status, $this->api->getStatus());
    }

    public function testGetStatusWithScriptsReturnsStatus(): void
    {
        $status = ['memory_usage' => ['used_memory' => 65536]];
        $this->api->setStatus($status);

        $this->assertSame($status, $this->api->getStatus(true));
    }

    public function testResetReturnsTrue(): void
    {
        $this->assertTrue($this->api->reset());
    }

    public function testInvalidateReturnsTrue(): void
    {
        $this->assertTrue($this->api->invalidate('/path/to/file.php'));
    }

    public function testCompileFileReturnsTrue(): void
    {
        $this->assertTrue($this->api->compileFile('/path/to/file.php'));
    }

    public function testGetConfigurationReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->api->getConfiguration());
    }

    public function testGetCallsLogsCalls(): void
    {
        $this->api->isEnabled();
        $this->api->getStatus();
        $this->api->reset();

        $calls = $this->api->getCalls();

        $this->assertCount(3, $calls);
        $this->assertSame('isEnabled', $calls[0]);
        $this->assertSame('getStatus', $calls[1]);
        $this->assertSame('reset', $calls[2]);
    }

    public function testGetCallsReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->api->getCalls());
    }
}
