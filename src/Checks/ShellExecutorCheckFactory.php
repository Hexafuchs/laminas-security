<?php

namespace Hexafuchs\LaminasSecurity\Checks;

use Hexafuchs\LaminasSecurity\Services\ShellExecutor;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ShellExecutorCheckFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AbstractCheck
    {
        return new $requestedName(
            $container->get(ShellExecutor::class)
        );
    }
}