<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Config\ConfigSchema;
use V3Cache\OpcacheManager\Config\ConfigValidator;
use V3Cache\OpcacheManager\Exception\ValidationException;

final class ConfigValidatorTest extends TestCase
{
    private ConfigValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ConfigValidator(new ConfigSchema());
    }

    public function testValidateAcceptsValidConfig(): void
    {
        $config = [
            'storage' => ['type' => 'memory'],
            'health' => ['min_hit_rate' => 80.0],
        ];

        $this->validator->validate($config);

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testValidateAcceptsEmptyConfig(): void
    {
        $this->validator->validate([]);

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testValidateThrowsOnInvalidType(): void
    {
        $config = [
            'storage' => ['type' => 123],
        ];

        try {
            $this->validator->validate($config);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
        }
    }

    public function testValidateThrowsOnUnknownSection(): void
    {
        $config = [
            'unknown' => ['key' => 'value'],
        ];

        try {
            $this->validator->validate($config);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
        }
    }

    public function testValidateThrowsOnUnknownKey(): void
    {
        $config = [
            'storage' => ['unknown_key' => 'value'],
        ];

        try {
            $this->validator->validate($config);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
        }
    }

    public function testValidateAcceptsStorageWithoutType(): void
    {
        $config = [
            'storage' => [],
        ];

        $this->validator->validate($config);

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testValidateAcceptsDynamicServerEntries(): void
    {
        $config = [
            'servers' => [
                'timeout' => 5.0,
                'web1' => 'http://10.0.0.1',
                'web2' => 'http://10.0.0.2',
            ],
        ];

        $this->validator->validate($config);

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testValidateStillRejectsInvalidTimeoutType(): void
    {
        $config = [
            'servers' => [
                'timeout' => 'not-a-number',
            ],
        ];

        try {
            $this->validator->validate($config);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
        }
    }

    public function testGetErrorsReturnsEmptyArrayWhenValid(): void
    {
        $config = [
            'storage' => ['type' => 'memory'],
        ];

        $this->validator->validate($config);

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testGetErrorsReturnsErrorsAfterFailedValidation(): void
    {
        $config = [
            'storage' => ['type' => 123],
        ];

        try {
            $this->validator->validate($config);
        } catch (ValidationException) {
        }

        $this->assertNotEmpty($this->validator->getErrors());
    }

    public function testValidateAcceptsNestedRedisConfig(): void
    {
        $config = [
            'storage' => [
                'type' => 'redis',
                'redis' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'password' => null,
                    'db' => 0,
                    'tls' => false,
                    'timeout' => 1.5,
                ],
            ],
        ];

        $this->validator->validate($config);

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testValidateAcceptsRedisTlsConfig(): void
    {
        $config = [
            'storage' => [
                'type' => 'redis',
                'redis' => [
                    'host' => 'redis.internal',
                    'port' => 6380,
                    'password_env' => 'REDIS_PASSWORD',
                    'db' => 1,
                    'tls' => true,
                    'ssl' => ['verify_peer' => true],
                    'read_write_timeout' => 2.0,
                    'persistent' => false,
                ],
            ],
        ];

        $this->validator->validate($config);

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testValidateRejectsInvalidRedisPort(): void
    {
        $this->assertInvalidConfig([
            'storage' => ['type' => 'redis', 'redis' => ['port' => 70000]],
        ]);
    }

    public function testValidateRejectsInvalidRedisDb(): void
    {
        $this->assertInvalidConfig([
            'storage' => ['type' => 'redis', 'redis' => ['db' => 99]],
        ]);
    }

    public function testValidateRejectsRedisHostWithScheme(): void
    {
        $this->assertInvalidConfig([
            'storage' => ['type' => 'redis', 'redis' => ['host' => 'http://127.0.0.1']],
        ]);
    }

    public function testValidateRejectsNonStringWarmupPath(): void
    {
        $this->assertInvalidConfig([
            'optimization' => ['warmup_paths' => [123]],
        ]);
    }

    public function testValidateRejectsNegativeHealthThreshold(): void
    {
        $this->assertInvalidConfig([
            'health' => ['min_hit_rate' => -1],
        ]);
    }

    public function testValidateRejectsInvalidStorageType(): void
    {
        $this->assertInvalidConfig([
            'storage' => ['type' => 'sqlite'],
        ]);
    }

    public function testValidateRejectsInvalidServerKey(): void
    {
        $this->assertInvalidConfig([
            'servers' => ['bad/key' => 'http://10.0.0.1'],
        ]);
    }

    public function testValidateRejectsInvalidPollInterval(): void
    {
        $this->assertInvalidConfig([
            'panel' => ['poll_interval' => 0],
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function assertInvalidConfig(array $config): void
    {
        try {
            $this->validator->validate($config);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
        }
    }
}
