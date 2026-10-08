<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Opcache\NativeOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class ApplicationFactory
{
    public static function create(): Application
    {
        $api = new NativeOpcacheApi();
        $service = new OpcacheService($api);

        $application = new Application();

        $application->register(new StatusCommand($service));
        $application->register(new ReloadCommand($service));
        $application->register(new ClearCommand($service));
        $application->register(new OptimizeCommand($service));
        $application->register(new InvalidateCommand($service));
        $application->register(new WarmupCommand($service));
        $application->register(new HealthCheckCommand($service));
        $application->register(new DoctorCommand($api));
        $application->register(new ValidateCommand($api));

        return $application;
    }
}
