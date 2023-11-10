<?php

namespace Hexafuchs\LaminasSecurity\Exceptions;

use Exception;
use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;
use Throwable;

class InvalidCheckException extends Exception
{
    public function __construct(string $className, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Expected check to be of type %s but got %s instead.',
            AbstractCheck::class, $className
        ), $code, $previous);
    }
}