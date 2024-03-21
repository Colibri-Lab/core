<?php

/**
 * Exceptions
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Exceptions
 */
namespace Colibri\Exceptions;

/**
 * Exception thrown to indicate that the server cannot or will not process the request due to something that is perceived to be a client error.
 */
class BadRequestException extends \Exception
{

    /**
     * The HTTP status code for a bad request.
     */
    public const BadRequestExceptionCode = 400;

    /**
     * Message indicating a bad request.
     */
    public const BadRequestExceptionMessage = 'Bad request';
}