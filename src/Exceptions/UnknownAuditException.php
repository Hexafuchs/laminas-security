<?php

namespace Hexafuchs\LaminasSecurity\Exceptions;

class UnknownAuditException extends UnknownElementException
{
    /**
     * @inheritdoc
     */
    protected const ELEMENT_NAME_SINGULAR = 'audit';

    /**
     * @inheritdoc
     */
    protected const ELEMENT_NAME_PLURAL = 'audits';
}