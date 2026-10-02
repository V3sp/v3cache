<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use V3Cache\OpcacheManager\Cli\ApplicationFactory;

final class ApplicationFactoryTest extends TestCase
{
    public function testCreatesConsoleApplication(): void
    {
        $application = ApplicationFactory::create();

        $this->assertInstanceOf(Application::class, $application);
        $this->assertSame('opcache-manager', $application->getName());
    }
}
