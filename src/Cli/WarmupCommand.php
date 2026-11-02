<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Config\ConfigLoaderFactory;
use V3Cache\OpcacheManager\Service\OpcacheService;

final class WarmupCommand implements CommandInterface
{
    private const MAX_FILES = 10000;

    /**
     * @param array<int, string> $allowedRoots
     */
    public function __construct(
        private readonly OpcacheService $service,
        private readonly array $allowedRoots = [],
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
        $roots = $this->resolveRoots($options);
        $files = [];

        $fileList = $options['file'] ?? $options['f'] ?? null;
        if ($fileList !== null) {
            $files = $this->readFileList((string) $fileList, $roots);
        }

        $dir = $options['dir'] ?? $options['d'] ?? null;
        if ($dir !== null) {
            $files = array_merge($files, $this->scanDirectory((string) $dir, $roots));
        }

        if ($files === []) {
            $files = $this->loadWarmupPathsFromConfig((string) ($options['config'] ?? 'config.php'), $roots);
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
     * @param array<string, string> $options
     * @return array<int, string>
     */
    private function resolveRoots(array $options): array
    {
        $root = $options['root'] ?? $options['r'] ?? null;
        $roots = $root !== null ? [(string) $root] : $this->allowedRoots;

        if ($roots === []) {
            $cwd = getcwd();
            $roots = $cwd !== false ? [$cwd] : [];
        }

        return PathGuard::normalizeRoots($roots);
    }

    /**
     * @param array<int, string> $roots
     * @return array<int, string>
     */
    private function readFileList(string $path, array $roots): array
    {
        $resolvedPath = PathGuard::resolveListFile($path);

        if ($resolvedPath === null) {
            return [];
        }

        $content = file_get_contents($resolvedPath);
        if ($content === false) {
            return [];
        }

        $files = [];

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $file = PathGuard::resolveFile($line, $roots);

            if ($file === null || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }

            $files[$file] = true;

            if (count($files) >= self::MAX_FILES) {
                break;
            }
        }

        return array_keys($files);
    }

    /**
     * @param array<int, string> $roots
     * @return array<int, string>
     */
    private function loadWarmupPathsFromConfig(string $configPath, array $roots): array
    {
        if (!file_exists($configPath)) {
            return [];
        }

        try {
            $config = ConfigLoaderFactory::create()->load($configPath);
        } catch (\RuntimeException) {
            return [];
        }

        $paths = $config['optimization']['warmup_paths'] ?? [];
        if (!is_array($paths)) {
            return [];
        }

        $files = [];

        foreach ($paths as $path) {
            if (!is_string($path)) {
                continue;
            }

            $dir = PathGuard::resolveDirectory($path, $roots);

            if ($dir !== null) {
                foreach ($this->scanDirectory($dir, $roots) as $file) {
                    $files[$file] = true;
                }

                continue;
            }

            $file = PathGuard::resolveFile($path, $roots);

            if ($file === null || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }

            $files[$file] = true;

            if (count($files) >= self::MAX_FILES) {
                break;
            }
        }

        return array_slice(array_keys($files), 0, self::MAX_FILES);
    }

    /**
     * @param array<int, string> $roots
     * @return array<int, string>
     */
    private function scanDirectory(string $dir, array $roots): array
    {
        $resolvedDir = PathGuard::resolveDirectory($dir, $roots);

        if ($resolvedDir === null) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($resolvedDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!($file instanceof \SplFileInfo) || !$file->isFile() || $file->isLink()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $real = PathGuard::resolveFile($file->getPathname(), $roots);

            if ($real === null) {
                continue;
            }

            $files[$real] = true;

            if (count($files) >= self::MAX_FILES) {
                break;
            }
        }

        return array_keys($files);
    }
}
