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

    /**
     * Tests if this script is executed in a cli environment.
     *
     * @return bool
     */
    public function isExecutedInCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Registers laminas-security's commands with laminas-cli
     *
     * @return array[]
     */
    public function getCliConfig(): array
    {
        return [
            'commands' => [
                'security:audit'  => \Hexafuchs\LaminasSecurity\Commands\SecurityAuditCommand::class,
                'security:report' => \Hexafuchs\LaminasSecurity\Commands\SecurityReportCommand::class
            ]
        ];
    }

    /**
     * Returns the dependency configuration required for laminas-security
     *
     * @return \string[][]
     */
    public function getDependencyConfig(): array
    {
        return [
            'factories' => [
                // Commands
                \Hexafuchs\LaminasSecurity\Commands\SecurityAuditCommand::class  => \Hexafuchs\LaminasSecurity\Commands\SecurityCommandFactory::class,
                \Hexafuchs\LaminasSecurity\Commands\SecurityReportCommand::class => \Hexafuchs\LaminasSecurity\Commands\SecurityCommandFactory::class,

                // Services
                \Hexafuchs\LaminasSecurity\Services\CheckLoader::class           => \Hexafuchs\LaminasSecurity\Services\CheckLoaderFactory::class,
                \Hexafuchs\LaminasSecurity\Services\ShellExecutor::class         => \Hexafuchs\LaminasSecurity\Services\ShellExecutorFactory::class
            ]
        ];
    }

    /**
     * Returns the default configuration for laminas-security itself
     *
     * @return array
     */
    public function getScannerConfig(): array
    {
        return [
            'audits' => [
                'ci'   => [],
                'dev'  => [],
                'prod' => []
            ],

            'checks' => []
        ];
    }
}