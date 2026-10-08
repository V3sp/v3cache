<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Config;

use V3Cache\OpcacheManager\Exception\ValidationException;

final class ConfigValidator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(
        private readonly ConfigSchema $schema,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function validate(array $config): void
    {
        $this->errors = [];

        foreach ($config as $section => $values) {
            if (!in_array($section, $this->schema->getSections(), true)) {
                $this->errors[$section] = \sprintf('Unknown section "%s"', $section);
                continue;
            }

            $this->validateSection($section, $values);
        }

        if ($this->errors !== []) {
            throw new ValidationException('Config validation failed', $this->errors);
        }
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function validateSection(string $section, array $values): void
    {
        foreach ($values as $key => $value) {
            $fullKey = $section . '.' . $key;

            try {
                $this->schema->getType($fullKey);
            } catch (\InvalidArgumentException $e) {
                if (!$this->schema->isOpen($section)) {
                    $this->errors[$fullKey] = \sprintf('Unknown key "%s"', $fullKey);
                }

                continue;
            }

            $expectedType = $this->schema->getType($fullKey);
            if (!$this->isTypeValid($value, $expectedType)) {
                $this->errors[$fullKey] = \sprintf(
                    'Invalid type for "%s": expected %s, got %s',
                    $fullKey,
                    $expectedType,
                    get_debug_type($value),
                );
            }
        }

        foreach ($this->schema->getKeys($section) as $key) {
            $fullKey = $section . '.' . $key;
            if ($this->schema->isRequired($fullKey) && !array_key_exists($key, $values)) {
                $this->errors[$fullKey] = \sprintf('Missing required key "%s"', $fullKey);
            }
        }
    }

    private function isTypeValid(mixed $value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($value),
            'int' => is_int($value),
            'float' => is_float($value) || is_int($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            default => false,
        };
    }
}
