<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Opcache;

final class MockOpcacheApi implements OpcacheApiInterface
{
    private bool $enabled = false;

    /** @var array<string, mixed>|null */
    private ?array $status = null;

    /** @var array<string, mixed> */
    private array $config = [];

    /** @var array<int, string> */
    private array $calls = [];

    public function isEnabled(): bool
    {
        $this->calls[] = 'isEnabled';

        return $this->enabled;
    }

    public function getStatus(bool $withScripts = false): ?array
    {
        $this->calls[] = 'getStatus';

        return $this->status;
    }

    public function reset(): bool
    {
        $this->calls[] = 'reset';

        return true;
    }

    public function invalidate(string $path, bool $force = true): bool
    {
        $this->calls[] = 'invalidate';

        return true;
    }

    public function compileFile(string $path): bool
    {
        $this->calls[] = 'compileFile';

        return true;
    }

    public function getConfiguration(): array
    {
        $this->calls[] = 'getConfiguration';

        return $this->config;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function setConfiguration(array $config): void
    {
        $this->config = $config;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @param array<string, mixed> $status
     */
    public function setStatus(array $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array<int, string>
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}
