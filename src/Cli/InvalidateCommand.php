<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

use V3Cache\OpcacheManager\Service\OpcacheService;

final class InvalidateCommand implements CommandInterface
{
    public function __construct(
        private readonly OpcacheService $service,
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

        $this->service->invalidate($path);

        echo OutputFormatter::success(\sprintf('Script "%s" invalidated successfully', $path)) . "\n";

        return 0;
    }
}
