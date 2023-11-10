<?php

namespace Hexafuchs\LaminasSecurity;

class Module
{
    public function getConfig(): array
    {
        $configProvider = new ConfigProvider();

        if (!$configProvider->isExecutedInCli()) {
            return [];
        }

        return [
            'laminas-cli'                           => $configProvider->getCliConfig(),
            'service_manager'                       => $configProvider->getDependencyConfig(),
            ConfigProvider::LAMINAS_SECURITY_CONFIG => $configProvider->getScannerConfig()
        ];
    }
}