<?php

namespace Hexafuchs\LaminasSecurity\Services;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ShellExecutorFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ShellExecutor
    {
        return new ShellExecutor();
    }
}