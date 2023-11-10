<?php

namespace Hexafuchs\LaminasSecurity\Checks\Dependencies;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;

abstract class AbstractDependenciesCheck extends AbstractCheck
{

    /**
     * @inheritDoc
     */
    public function getReportName(): string
    {
        return 'dependencies';
    }
}