<?php

namespace Hexafuchs\LaminasSecurity\Services;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CheckLoaderFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\InvalidCheckException
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CheckLoader
    {
        return new CheckLoader(
            $container->get('config')['laminas-security'],
            fn(string $clsName) => $container->get($clsName)
        );
    }
}