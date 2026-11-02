<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class SimpleYamlParser
{
    private const MAX_BYTES = 1048576;

    /**
     * @return array<string, mixed>
     */
    public function parse(string $content): array
    {
        if (strlen($content) > self::MAX_BYTES) {
            throw new \RuntimeException('YAML config is too large');
        }

        $lines = explode("\n", $content);
        $result = [];
        $currentSection = null;
        $currentList = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, '- ')) {
                if ($currentSection !== null && $currentList !== null) {
                    $currentList[] = trim(substr($line, 2));
                    $result[$currentSection] = $currentList;
                }

                continue;
            }

            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if ($value === '') {
                    $currentSection = $key;
                    $currentList = [];
                    $result[$key] = [];
                } else {
                    if ($currentSection !== null) {
                        $section = $result[$currentSection] ?? [];
                        if (!is_array($section)) {
                            $section = [];
                        }
                        $section[$key] = $this->parseValue($value);
                        $result[$currentSection] = $section;
                    } else {
                        $result[$key] = $this->parseValue($value);
                    }
                }
            }
        }

        return $result;
    }

    private function parseValue(string $value): string|int|float|bool
    {
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        if (preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }
}
