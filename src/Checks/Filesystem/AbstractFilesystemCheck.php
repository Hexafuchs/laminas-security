<?php

namespace Hexafuchs\LaminasSecurity\Checks\Filesystem;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;

abstract class AbstractFilesystemCheck extends AbstractCheck
{

    /**
     * @inheritDoc
     */
    public function getReportName(): string
    {
        return 'filesystem';
    }
}