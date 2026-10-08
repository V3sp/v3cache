<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Config\ConfigLoader;
use V3Cache\OpcacheManager\Config\ConfigParserRegistry;
use V3Cache\OpcacheManager\Config\ConfigValidator;
use V3Cache\OpcacheManager\Config\ConfigSchema;
use V3Cache\OpcacheManager\Config\PhpConfigParser;
use V3Cache\OpcacheManager\Config\JsonConfigParser;
use V3Cache\OpcacheManager\Config\YamlConfigParser;
use V3Cache\OpcacheManager\Exception\ValidationException;
use V3Cache\OpcacheManager\Opcache\OpcacheApiInterface;
use V3Cache\OpcacheManager\Storage\StorageFactory;

final class ValidateCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheApiInterface $api,
    ) {
    }

    public function name(): string
    {
        return 'validate';
    }

    public function description(): string
    {
        return 'Validate OPcache Manager configuration';
    }

    public function execute(array $args, array $options): int
    {
        $configPath = $options['config'] ?? $options['c'] ?? 'config.php';

        if (!file_exists($configPath)) {
            fwrite(STDERR, OutputFormatter::error(\sprintf('Config file not found: %s', $configPath)) . "\n");

            return 1;
        }

        $exitCode = 0;

        echo OutputFormatter::info('Configuration Validation') . "\n\n";

        $loader = new ConfigLoader(
            new ConfigParserRegistry([
                new PhpConfigParser(),
                new JsonConfigParser(),
                new YamlConfigParser(),
            ]),
            new ConfigValidator(new ConfigSchema()),
        );

        try {
            $config = $loader->load($configPath);
            $this->check('Config parsing', 'valid', true);
        } catch (ValidationException $e) {
            $this->check('Config parsing', $e->getMessage(), false);
            $exitCode = 1;

            echo "\n";
            echo OutputFormatter::error('Validation failed') . "\n";

            return $exitCode;
        }

        $storageFactory = new StorageFactory();
        try {
            $storageFactory->create($config['storage'] ?? []);
            $this->check('Storage configuration', 'valid', true);
        } catch (ValidationException $e) {
            $this->check('Storage configuration', $e->getMessage(), false);
            $exitCode = 1;
        }

        $servers = $config['servers'] ?? [];
        if ($servers === []) {
            $this->check('Multi-server', 'not configured', true);
        } else {
            $this->check('Multi-server', \sprintf('%d server(s) configured', count($servers)), true);
        }

        $this->check('OPcache enabled', $this->api->isEnabled() ? 'yes' : 'no', $this->api->isEnabled());

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
