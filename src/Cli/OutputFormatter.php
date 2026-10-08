<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

final class OutputFormatter
{
    private const COLORS = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m",
    ];

    public static function success(string $message): string
    {
        return self::COLORS['green'] . $message . self::COLORS['reset'];
    }

    public static function error(string $message): string
    {
        return self::COLORS['red'] . $message . self::COLORS['reset'];
    }

    public static function warning(string $message): string
    {
        return self::COLORS['yellow'] . $message . self::COLORS['reset'];
    }

    public static function info(string $message): string
    {
        return self::COLORS['blue'] . $message . self::COLORS['reset'];
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string>> $rows
     */
    public static function table(array $headers, array $rows): string
    {
        $widths = array_map('strlen', $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        $output = self::renderRow($headers, $widths) . "\n";
        $output .= self::renderSeparator($widths) . "\n";

        foreach ($rows as $row) {
            $output .= self::renderRow($row, $widths) . "\n";
        }

        return $output;
    }

    /**
     * @param array<int, string> $cells
     * @param array<int, int> $widths
     */
    private static function renderRow(array $cells, array $widths): string
    {
        $parts = [];
        foreach ($cells as $i => $cell) {
            $parts[] = str_pad($cell, $widths[$i]);
        }

        return implode('  ', $parts);
    }

    /**
     * @param array<int, int> $widths
     */
    private static function renderSeparator(array $widths): string
    {
        $parts = [];
        foreach ($widths as $width) {
            $parts[] = str_repeat('-', $width);
        }

        return implode('  ', $parts);
    }
}
