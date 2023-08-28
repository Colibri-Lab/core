<?php

namespace Colibri\Exceptions;

class BadRequestException extends \Exception
{

    public const BadRequestExceptionCode = 400;

    public const BadRequestExceptionMessage = 'Bad request';
}