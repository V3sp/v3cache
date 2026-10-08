<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

final class ArgumentParser
{
    /**
     * @param array<int, string> $argv
     * @return array{args: array<int, string>, options: array<string, string>}
     */
    public static function parse(array $argv): array
    {
        $args = [];
        $options = [];

        $count = count($argv);
        for ($i = 1; $i < $count; $i++) {
            $token = $argv[$i];

            if (str_starts_with($token, '--')) {
                $name = substr($token, 2);
                if (str_contains($name, '=')) {
                    [$name, $value] = explode('=', $name, 2);
                    $options[$name] = $value;
                } elseif ($i + 1 < $count && !str_starts_with($argv[$i + 1], '-')) {
                    $options[$name] = $argv[$i + 1];
                    $i++;
                } else {
                    $options[$name] = '1';
                }
            } elseif (str_starts_with($token, '-') && strlen($token) === 2) {
                $name = substr($token, 1);
                if ($i + 1 < $count && !str_starts_with($argv[$i + 1], '-')) {
                    $options[$name] = $argv[$i + 1];
                    $i++;
                } else {
                    $options[$name] = '1';
                }
            } else {
                $args[] = $token;
            }
        }

        return ['args' => $args, 'options' => $options];
    }
}
