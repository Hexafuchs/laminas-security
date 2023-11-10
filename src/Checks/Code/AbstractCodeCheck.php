<?php

namespace Hexafuchs\LaminasSecurity\Checks\Code;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;

abstract class AbstractCodeCheck extends AbstractCheck
{

    /**
     * @inheritDoc
     */
    public function getReportName(): string
    {
        return 'code';
    }
}