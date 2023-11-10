<?php

namespace Hexafuchs\LaminasSecurity\Checks;

use Hexafuchs\LaminasSecurity\ConfigProvider;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class BaseUrlAwareCheckFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AbstractCheck
    {
        $config = $container->get('config');

        return new $requestedName(
            $config[ConfigProvider::LAMINAS_SECURITY_CONFIG]['app']['base_url']
        );
    }
}