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

            if (!is_array($values)) {
                $this->errors[$section] = \sprintf('Section "%s" must be an array', $section);
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
                $expectedType = $this->schema->getType($fullKey);
            } catch (\InvalidArgumentException $e) {
                if (!$this->schema->isOpen($section)) {
                    $this->errors[$fullKey] = \sprintf('Unknown key "%s"', $fullKey);
                }

                continue;
            }

            if (!$this->isTypeValid($value, $expectedType)) {
                $this->errors[$fullKey] = \sprintf(
                    'Invalid type for "%s": expected %s, got %s',
                    $fullKey,
                    $expectedType,
                    get_debug_type($value),
                );

                continue;
            }

            $this->validateValue($fullKey, $value);
        }

        if ($this->schema->isOpen($section)) {
            $this->validateOpenSection($section, $values);
        }

        foreach ($this->schema->getKeys($section) as $key) {
            $fullKey = $section . '.' . $key;
            if ($this->schema->isRequired($fullKey) && !array_key_exists($key, $values)) {
                $this->errors[$fullKey] = \sprintf('Missing required key "%s"', $fullKey);
            }
        }
    }

    private function validateValue(string $key, mixed $value): void
    {
        switch ($key) {
            case 'storage.type':
                if (!in_array($value, ['memory', 'redis'], true)) {
                    $this->errors[$key] = 'Storage type must be "memory" or "redis"';
                }

                break;

            case 'storage.redis':
                if (is_array($value)) {
                    $this->validateRedis($value);
                }

                break;

            case 'optimization.warmup_paths':
                if (is_array($value)) {
                    $this->validateStringList($key, $value);
                }

                break;

            case 'panel.poll_interval':
                if (is_int($value) && ($value < 1 || $value > 3600)) {
                    $this->errors[$key] = 'poll_interval must be between 1 and 3600';
                }

                break;

            case 'servers.timeout':
                $this->validateNumber($key, $value, 0.0, null, false);
                break;

            case 'health.min_hit_rate':
            case 'health.max_wasted_percent':
            case 'health.max_memory_usage_percent':
                $this->validateNumber($key, $value, 0.0, 100.0);
                break;

            case 'health.max_restarts':
                if (is_int($value) && $value < 0) {
                    $this->errors[$key] = 'max_restarts must be greater than or equal to 0';
                }

                break;
        }
    }

    /**
     * @param array<string, mixed> $redis
     */
    private function validateRedis(array $redis): void
    {
        $allowed = [
            'host',
            'port',
            'password',
            'password_env',
            'db',
            'tls',
            'ssl',
            'timeout',
            'read_write_timeout',
            'persistent',
        ];

        foreach ($redis as $key => $value) {
            $fullKey = 'storage.redis.' . $key;

            if (!in_array($key, $allowed, true)) {
                $this->errors[$fullKey] = \sprintf('Unknown key "%s"', $fullKey);
                continue;
            }

            switch ($key) {
                case 'host':
                    if (
                        !is_string($value)
                        || $value === ''
                        || strlen($value) > 255
                        || str_contains($value, '://')
                        || $this->hasControlChars($value)
                    ) {
                        $this->errors[$fullKey] = 'Redis host must be a non-empty hostname or IP address';
                    }

                    break;

                case 'port':
                    if (!is_int($value) || $value < 1 || $value > 65535) {
                        $this->errors[$fullKey] = 'Redis port must be between 1 and 65535';
                    }

                    break;

                case 'password':
                    if ($value !== null && (!is_string($value) || strlen($value) > 1024)) {
                        $this->errors[$fullKey] = 'Redis password must be a string or null';
                    }

                    break;

                case 'password_env':
                    if (!is_string($value) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) !== 1) {
                        $this->errors[$fullKey] = 'password_env must be a valid environment variable name';
                    }

                    break;

                case 'db':
                    if (!is_int($value) || $value < 0 || $value > 15) {
                        $this->errors[$fullKey] = 'Redis db must be between 0 and 15';
                    }

                    break;

                case 'tls':
                case 'persistent':
                    if (!is_bool($value)) {
                        $this->errors[$fullKey] = \sprintf('Redis %s must be a boolean', $key);
                    }

                    break;

                case 'ssl':
                    if (!is_array($value)) {
                        $this->errors[$fullKey] = 'Redis ssl must be an array of SSL context options';
                    }

                    break;

                case 'timeout':
                case 'read_write_timeout':
                    $this->validateNumber($fullKey, $value, 0.0, null, false);
                    break;
            }
        }
    }

    /**
     * @param array<int|string, mixed> $values
     */
    private function validateStringList(string $key, array $values): void
    {
        if (count($values) > 1000) {
            $this->errors[$key] = 'List must contain at most 1000 entries';
            return;
        }

        foreach ($values as $index => $value) {
            if (!is_string($value) || $value === '' || strlen($value) > 1024 || $this->hasControlChars($value)) {
                $this->errors[$key . '.' . $index] = 'List entries must be non-empty strings '
                    . 'without control characters';
            }
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function validateOpenSection(string $section, array $values): void
    {
        foreach ($values as $key => $value) {
            if ($key === 'timeout') {
                continue;
            }

            $fullKey = $section . '.' . $key;

            if (!is_string($key) || preg_match('/^[A-Za-z0-9._-]{1,64}$/', $key) !== 1) {
                $this->errors[$fullKey] = 'Open section keys must match [A-Za-z0-9._-]{1,64}';
                continue;
            }

            if (is_string($value)) {
                if (strlen($value) > 2048 || $this->hasControlChars($value)) {
                    $this->errors[$fullKey] = 'Open section values must be strings without control characters';
                }

                continue;
            }

            if (!is_array($value)) {
                $this->errors[$fullKey] = 'Open section values must be strings or arrays';
            }
        }
    }

    private function validateNumber(string $key, mixed $value, float $min, ?float $max, bool $minInclusive = true): void
    {
        if (!is_int($value) && !is_float($value)) {
            $this->errors[$key] = 'Value must be a number';
            return;
        }

        $number = (float) $value;
        $belowMin = $minInclusive ? $number < $min : $number <= $min;

        if (!is_finite($number) || $belowMin || ($max !== null && $number > $max)) {
            $this->errors[$key] = $max === null
                ? \sprintf('Value must be %s %s', $minInclusive ? 'at least' : 'greater than', $min)
                : \sprintf(
                    'Value must be %s %s and at most %s',
                    $minInclusive ? 'at least' : 'greater than',
                    $min,
                    $max,
                );
        }
    }

    private function hasControlChars(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
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
