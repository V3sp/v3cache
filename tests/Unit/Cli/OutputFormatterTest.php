<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use V3Cache\OpcacheManager\Cli\OutputFormatter;

final class OutputFormatterTest extends TestCase
{
    public function testSanitizeStripsAnsiEscape(): void
    {
        $this->assertSame('[31mred[0m', OutputFormatter::sanitize("\033[31mred\033[0m"));
    }

    public function testSanitizeStripsOscSequenceEscape(): void
    {
        $this->assertSame(']52;clipboard', OutputFormatter::sanitize("\033]52;clipboard\007"));
    }

    public function testSanitizePreservesNewlinesAndTabs(): void
    {
        $this->assertSame("line1\nline2\tvalue", OutputFormatter::sanitize("line1\nline2\tvalue"));
    }

    public function testSuccessSanitizesMessage(): void
    {
        $this->assertSame(
            "\033[32m[31mred[0m\033[0m",
            OutputFormatter::success("\033[31mred\033[0m"),
        );
    }
}
