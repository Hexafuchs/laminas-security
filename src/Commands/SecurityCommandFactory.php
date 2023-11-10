<?php

namespace Hexafuchs\LaminasSecurity\Commands;

use Hexafuchs\LaminasSecurity\Services\CheckLoader;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SecurityCommandFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AbstractSecurityCommand
    {
        return new $requestedName(
            $container->get(CheckLoader::class)
        );
    }
}