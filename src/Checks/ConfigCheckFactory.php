<?php

namespace Hexafuchs\LaminasSecurity\Checks;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ConfigCheckFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AbstractCheck
    {
        return new $requestedName(
            $container->get('config')
        );
    }
}