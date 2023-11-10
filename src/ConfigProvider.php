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
                \Hexafuchs\LaminasSecurity\Commands\SecurityAuditCommand::class                           => \Hexafuchs\LaminasSecurity\Commands\SecurityCommandFactory::class,
                \Hexafuchs\LaminasSecurity\Commands\SecurityReportCommand::class                          => \Hexafuchs\LaminasSecurity\Commands\SecurityCommandFactory::class,

                // Services
                \Hexafuchs\LaminasSecurity\Services\CheckLoader::class                                    => \Hexafuchs\LaminasSecurity\Services\CheckLoaderFactory::class,
                \Hexafuchs\LaminasSecurity\Services\ShellExecutor::class                                  => \Hexafuchs\LaminasSecurity\Services\ShellExecutorFactory::class,

                // Checks
                \Hexafuchs\LaminasSecurity\Checks\Code\TaintAnalysisCheck::class                          => \Hexafuchs\LaminasSecurity\Checks\ShellExecutorCheckFactory::class,
                \Hexafuchs\LaminasSecurity\Checks\Configuration\SecureCookiesCheck::class                 => \Hexafuchs\LaminasSecurity\Checks\ConfigCheckFactory::class,
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\LockedDependenciesCheck::class             => \Hexafuchs\LaminasSecurity\Checks\ShellExecutorCheckFactory::class,
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\StableDependenciesCheck::class             => \Hexafuchs\LaminasSecurity\Checks\ShellExecutorCheckFactory::class,
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\VulnerableBackendDependenciesCheck::class  => \Hexafuchs\LaminasSecurity\Checks\ShellExecutorCheckFactory::class,
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\VulnerableFrontendDependenciesCheck::class => \Hexafuchs\LaminasSecurity\Checks\ShellExecutorCheckFactory::class,
                \Hexafuchs\LaminasSecurity\Checks\Environment\InsecurePhpConfigCheck::class               => \Hexafuchs\LaminasSecurity\Checks\DefaultCheckFactory::class,
                \Hexafuchs\LaminasSecurity\Checks\Environment\InsecurePasswordsCheck::class               => \Hexafuchs\LaminasSecurity\Checks\ConfigCheckFactory::class,
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
                'ci'   => [
                    'code',
                    'configuration',
                    'dependencies',
                    'filesystem'
                ],
                'dev'  => [
                    'code',
                    'dependencies',
                    'filesystem'
                ],
                'prod' => [
                    'configuration',
                    'dependencies',
                    'environment',
                    'filesystem',
                    'webserver'
                ]
            ],

            'checks' => [
                // Code
                \Hexafuchs\LaminasSecurity\Checks\Code\TaintAnalysisCheck::class,

                // Configuration
                \Hexafuchs\LaminasSecurity\Checks\Configuration\SecureCookiesCheck::class,

                // Dependencies
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\LockedDependenciesCheck::class,
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\StableDependenciesCheck::class,
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\VulnerableBackendDependenciesCheck::class,
                \Hexafuchs\LaminasSecurity\Checks\Dependencies\VulnerableFrontendDependenciesCheck::class,

                // Environment
                \Hexafuchs\LaminasSecurity\Checks\Environment\InsecurePasswordsCheck::class,
                \Hexafuchs\LaminasSecurity\Checks\Environment\InsecurePhpConfigCheck::class,
            ],

            'secrets' => [
                'require_length'      => 16,
                'require_uppercase'   => 1,
                'require_lowercase'   => 1,
                'require_numerical'   => 1,
                'secret_params_regex' => '/^[A-Za-z_]*(pass(word)?|secret)$/',
                'use_hibp_api'        => false
            ]
        ];
    }
}