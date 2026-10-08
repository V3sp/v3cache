<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Cli;

final class Application
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    public function register(CommandInterface $command): void
    {
        $this->commands[$command->name()] = $command;
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $commandName = $argv[0] ?? '';

        if ($commandName === '' || $commandName === 'help' || $commandName === '--help' || $commandName === '-h') {
            $this->showHelp();

            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            fwrite(STDERR, OutputFormatter::error("Unknown command: {$commandName}") . "\n");
            $this->showHelp();

            return 1;
        }

        $parsed = ArgumentParser::parse($argv);
        $command = $this->commands[$commandName];

        try {
            return $command->execute($parsed['args'], $parsed['options']);
        } catch (\Throwable $e) {
            fwrite(STDERR, OutputFormatter::error($e->getMessage()) . "\n");

            return 1;
        }
    }

    private function showHelp(): void
    {
        echo "Usage: php bin/opcache-manager <command> [options]\n\n";
        echo "Commands:\n";

        foreach ($this->commands as $command) {
            echo \sprintf("  %-15s %s\n", $command->name(), $command->description());
        }
    }
}
