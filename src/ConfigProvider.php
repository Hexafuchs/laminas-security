<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Hexafuchs\LaminasSecurity;

class ConfigProvider
{
    public function __invoke(): array
    {
        if (!$this->isExecutedInCli()) {
            return [];
        }

        return [
            'laminas-cli'      => $this->getCliConfig(),
            'laminas-security' => $this->getScannerConfig(),
            'dependencies'     => $this->getDependencyConfig()
        ];
    }

    public function isExecutedInCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    public function getCliConfig() : array
    {
        return [
            'commands' => []
        ];
    }

    public function getDependencyConfig(): array
    {
        return [
            'factories' => []
        ];
    }

    public function getScannerConfig(): array
    {
        return [];
    }
}