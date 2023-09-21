<?php

namespace Colibri\Exceptions;

class ApplicationErrorException extends \Exception
{
    public const ValidationError = 'Application validation error';
    public const ApplicationError = 'Application Error';
}
