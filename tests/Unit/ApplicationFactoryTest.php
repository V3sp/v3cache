<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\Application;
use V3Cache\OpcacheManager\Cli\ApplicationFactory;

final class ApplicationFactoryTest extends TestCase
{
    public function testCreatesApplication(): void
    {
        $application = ApplicationFactory::create();

        $this->assertInstanceOf(Application::class, $application);
    }

    public function testRegistersAllCommands(): void
    {
        $application = ApplicationFactory::create();

        $this->assertTrue($application->has('status'));
        $this->assertTrue($application->has('reload'));
        $this->assertTrue($application->has('clear'));
        $this->assertTrue($application->has('optimize'));
        $this->assertTrue($application->has('invalidate'));
        $this->assertTrue($application->has('warmup'));
        $this->assertTrue($application->has('health-check'));
        $this->assertTrue($application->has('doctor'));
        $this->assertTrue($application->has('validate'));
    }
}
