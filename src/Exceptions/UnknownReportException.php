<?php

namespace Hexafuchs\LaminasSecurity\Exceptions;

class UnknownReportException extends UnknownElementException
{
    /**
     * @inheritdoc
     */
    protected const ELEMENT_NAME_SINGULAR = 'report';

    /**
     * @inheritdoc
     */
    protected const ELEMENT_NAME_PLURAL = 'reports';
}