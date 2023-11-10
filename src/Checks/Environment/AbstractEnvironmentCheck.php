<?php

namespace Hexafuchs\LaminasSecurity\Checks\Environment;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;

abstract class AbstractEnvironmentCheck extends AbstractCheck
{

    /**
     * @inheritDoc
     */
    public function getReportName(): string
    {
        return 'environment';
    }
}