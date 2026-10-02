<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use Symfony\Component\Console\Application;

final class ApplicationFactory
{
    public static function create(): Application
    {
        $application = new Application('opcache-manager', '0.1.0');
        $application->setCatchExceptions(true);

        return $application;
    }
}
