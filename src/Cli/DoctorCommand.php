<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Opcache\OpcacheApiInterface;

final class DoctorCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheApiInterface $api,
    ) {
    }

    public function name(): string
    {
        return 'doctor';
    }

    public function description(): string
    {
        return 'Diagnose OPcache environment';
    }

    public function execute(array $args, array $options): int
    {
        $exitCode = 0;

        echo OutputFormatter::info('OPcache Environment Diagnostics') . "\n\n";

        $this->check('PHP version', PHP_VERSION, true);

        $opcacheLoaded = extension_loaded('Zend OPcache');
        $this->check('opcache extension loaded', $opcacheLoaded ? 'yes' : 'no', $opcacheLoaded);
        $this->check('opcache.enable', ini_get('opcache.enable') ?: 'off', ini_get('opcache.enable') === '1');

        $enableCli = ini_get('opcache.enable_cli');
        $this->check('opcache.enable_cli', $enableCli ?: 'off', $enableCli === '1');

        if (!$this->api->isEnabled()) {
            $this->check('opcache status', 'disabled', false);
            $exitCode = 1;
        } else {
            $this->check('opcache status', 'enabled', true);

            $config = $this->api->getConfiguration();
            $directives = $config['directives'] ?? [];

            $memoryConsumption = $directives['opcache.memory_consumption'] ?? 'not set';
            $maxAcceleratedFiles = $directives['opcache.max_accelerated_files'] ?? 'not set';
            $validateTimestamps = $directives['opcache.validate_timestamps'] ?? 'not set';
            $revalidateFreq = $directives['opcache.revalidate_freq'] ?? 'not set';

            $this->check('opcache.memory_consumption', (string) $memoryConsumption, true);
            $this->check('opcache.max_accelerated_files', (string) $maxAcceleratedFiles, true);
            $this->check('opcache.validate_timestamps', (string) $validateTimestamps, true);
            $this->check('opcache.revalidate_freq', (string) $revalidateFreq, true);
        }

        echo "\n";

        if ($exitCode === 0) {
            echo OutputFormatter::success('All checks passed') . "\n";
        } else {
            echo OutputFormatter::error('Some checks failed') . "\n";
        }

        return $exitCode;
    }

    private function check(string $name, string $value, bool $ok): void
    {
        $status = $ok ? OutputFormatter::success('OK') : OutputFormatter::error('FAIL');
        echo \sprintf("  %-30s %s (%s)\n", $name, $status, $value);
    }
}
