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
            'laminas-cli'      => $configProvider->getCliConfig(),
            'laminas-security' => $configProvider->getScannerConfig(),
            'service_manager'  => $configProvider->getDependencyConfig()
        ];
    }
}