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
}
