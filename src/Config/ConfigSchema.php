<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class ConfigSchema
{
    /**
     * @var array<string, array<string, array{type: string, required: bool, default: mixed}>>
     */
    private array $schema = [
        'storage' => [
            'type' => ['type' => 'string', 'required' => false, 'default' => 'memory'],
            'redis' => ['type' => 'array', 'required' => false, 'default' => null],
        ],
        'health' => [
            'min_hit_rate' => ['type' => 'float', 'required' => false, 'default' => 80.0],
            'max_wasted_percent' => ['type' => 'float', 'required' => false, 'default' => 20.0],
            'max_restarts' => ['type' => 'int', 'required' => false, 'default' => 0],
            'max_memory_usage_percent' => ['type' => 'float', 'required' => false, 'default' => 90.0],
        ],
        'servers' => [
            'timeout' => ['type' => 'float', 'required' => false, 'default' => 5.0],
        ],
        'optimization' => [
            'warmup_paths' => ['type' => 'array', 'required' => false, 'default' => []],
        ],
        'panel' => [
            'poll_interval' => ['type' => 'int', 'required' => false, 'default' => 5],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public function getSections(): array
    {
        return array_keys($this->schema);
    }

    public function isOpen(string $section): bool
    {
        return in_array($section, ['servers'], true);
    }

    /**
     * @return array<int, string>
     */
    public function getKeys(string $section): array
    {
        if (!isset($this->schema[$section])) {
            throw new \InvalidArgumentException(\sprintf('Unknown config section "%s"', $section));
        }

        return array_keys($this->schema[$section]);
    }

    public function getDefaultValue(string $key): mixed
    {
        [$section, $name] = $this->parseKey($key);

        return $this->schema[$section][$name]['default'];
    }

    public function isRequired(string $key): bool
    {
        [$section, $name] = $this->parseKey($key);

        return $this->schema[$section][$name]['required'];
    }

    public function getType(string $key): string
    {
        [$section, $name] = $this->parseKey($key);

        return $this->schema[$section][$name]['type'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseKey(string $key): array
    {
        $parts = explode('.', $key);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(\sprintf('Invalid config key "%s"', $key));
        }

        [$section, $name] = $parts;

        if (!isset($this->schema[$section][$name])) {
            throw new \InvalidArgumentException(\sprintf('Unknown config key "%s"', $key));
        }

        return [$section, $name];
    }
}
