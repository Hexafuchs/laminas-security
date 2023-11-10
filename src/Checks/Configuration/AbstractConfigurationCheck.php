<?php

namespace Hexafuchs\LaminasSecurity\Checks\Configuration;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;

abstract class AbstractConfigurationCheck extends AbstractCheck
{

    /**
     * @inheritDoc
     */
    public function getReportName(): string
    {
        return 'configuration';
    }
}