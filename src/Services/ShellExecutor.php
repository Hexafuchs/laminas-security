<?php

namespace Hexafuchs\LaminasSecurity\Services;

use Symfony\Component\Process\Process;

class ShellExecutor
{
    /**
     * @var array<string, string> $commandPaths
     */
    private array $commandPaths = [];

    /**
     * Returns if the given commandName exists and is executable.
     * This function automatically caches found paths to the given commands.
     *
     * @param string $commandName
     * @return bool
     */
    public function commandExists(string $commandName): bool
    {
        if (array_key_exists($commandName, $this->commandPaths)) {
            return $this->commandPaths[$commandName] !== null;
        }

        if (file_exists($commandName) && is_executable($commandName)) {
            $this->commandPaths[$commandName] = $commandName;
            return true;
        }

        if (str_starts_with(PHP_OS, 'WIN')) {
            $response = $this->runCommand('where', $commandName);
        } else {
            $response = $this->runCommand('which', $commandName);
        }

        if ($response['exit_code'] !== 0) {
            $this->commandPaths[$commandName] = null;
            return false;
        }

        $result = trim($response['stdout']);

        if (is_executable($result)) {
            $this->commandPaths[$commandName] = $result;
            return true;
        } else {
            $this->commandPaths[$commandName] = null;
            return false;
        }
    }

    /**
     * Runs the given command with the given args and returns StatusCode, StdOut and StdErr.
     * Uses cache to retrieve paths to a command.
     *
     * @param string $commandName
     * @param string ...$args
     * @return array
     *
     * @psalm-taint-sink shell $commandName
     * @psalm-taint-sink shell $args
     */
    public function runCommand(string $commandName, string ...$args): array
    {
        $process = new Process([
            $this->commandPaths[$commandName] ?? $commandName,
            ...$args
        ], timeout: 120);
        $process->run();

        return [
            'exit_code' => $process->getExitCode(),
            'stdout'    => $process->getOutput(),
            'stderr'    => $process->getErrorOutput()
        ];
    }
}