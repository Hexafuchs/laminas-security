<?php

namespace Hexafuchs\LaminasSecurity\Checks\Webserver;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;

abstract class AbstractWebserverCheck extends AbstractCheck
{

    /**
     * @inheritDoc
     */
    public function getReportName(): string
    {
        return 'webserver';
    }
}