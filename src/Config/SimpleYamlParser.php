<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

final class SimpleYamlParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $content): array
    {
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

    private function parseValue(string $value): string|int|bool
    {
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }
}
