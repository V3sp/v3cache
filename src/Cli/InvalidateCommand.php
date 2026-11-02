<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Service\OpcacheService;

final class InvalidateCommand implements CommandInterface
{
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
        return 'invalidate';
    }

    public function description(): string
    {
        return 'Invalidate a single script in OPcache';
    }

    public function execute(array $args, array $options): int
    {
        $path = $args[0] ?? '';

        if ($path === '') {
            fwrite(STDERR, OutputFormatter::error('Usage: php bin/opcache-manager invalidate <path>') . "\n");

            return 1;
        }

        $roots = $this->resolveRoots($options);
        $file = PathGuard::resolveFile($path, $roots);

        if ($file === null) {
            fwrite(STDERR, OutputFormatter::error(\sprintf('Script not found or not allowed: %s', $path)) . "\n");

            return 1;
        }

        $this->service->invalidate($file);

        echo OutputFormatter::success(\sprintf('Script "%s" invalidated successfully', $file)) . "\n";

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
}
