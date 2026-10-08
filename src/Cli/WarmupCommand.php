<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Config\ConfigLoader;
use V3Cache\OpcacheManager\Config\ConfigParserRegistry;
use V3Cache\OpcacheManager\Config\ConfigSchema;
use V3Cache\OpcacheManager\Config\ConfigValidator;
use V3Cache\OpcacheManager\Config\JsonConfigParser;
use V3Cache\OpcacheManager\Config\PhpConfigParser;
use V3Cache\OpcacheManager\Config\YamlConfigParser;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class WarmupCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheService $service,
    ) {
    }

    public function name(): string
    {
        return 'warmup';
    }

    public function description(): string
    {
        return 'Compile files into OPcache';
    }

    public function execute(array $args, array $options): int
    {
        $files = [];

        $fileList = $options['file'] ?? $options['f'] ?? null;
        if ($fileList !== null) {
            $files = $this->readFileList($fileList);
        }

        $dir = $options['dir'] ?? $options['d'] ?? null;
        if ($dir !== null) {
            $files = array_merge($files, $this->scanDirectory($dir));
        }

        if ($files === []) {
            $files = $this->loadWarmupPathsFromConfig($options['config'] ?? 'config.php');
        }

        if ($files === []) {
            echo OutputFormatter::warning('No files to warm up') . "\n";

            return 0;
        }

        $compiled = 0;
        $skipped = 0;
        $startTime = microtime(true);

        foreach ($files as $file) {
            if ($this->service->compileFile($file)) {
                $compiled++;
            } else {
                $skipped++;
            }
        }

        $elapsed = microtime(true) - $startTime;

        echo OutputFormatter::success(\sprintf('Compiled: %d', $compiled)) . "\n";
        echo OutputFormatter::warning(\sprintf('Skipped: %d', $skipped)) . "\n";
        echo \sprintf("Time: %.3f seconds\n", $elapsed);

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function readFileList(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $lines = explode("\n", $content);
        $files = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && file_exists($line)) {
                $files[] = $line;
            }
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function loadWarmupPathsFromConfig(string $configPath): array
    {
        if (!file_exists($configPath)) {
            return [];
        }

        try {
            $loader = new ConfigLoader(
                new ConfigParserRegistry([
                    new PhpConfigParser(),
                    new JsonConfigParser(),
                    new YamlConfigParser(),
                ]),
                new ConfigValidator(new ConfigSchema()),
            );
            $config = $loader->load($configPath);
        } catch (\RuntimeException) {
            return [];
        }

        $paths = $config['optimization']['warmup_paths'] ?? [];
        if (!is_array($paths)) {
            return [];
        }

        return array_values(array_filter($paths, 'is_string'));
    }

    /**
     * @return array<int, string>
     */
    private function scanDirectory(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
