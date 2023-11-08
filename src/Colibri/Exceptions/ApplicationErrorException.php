<?php

namespace Colibri\Exceptions;

class ApplicationErrorException extends \Exception
{

    public const ErrorCode = 500;

    public const ValidationError = 'Application validation error';
    public const ApplicationError = 'Application Error';
}
