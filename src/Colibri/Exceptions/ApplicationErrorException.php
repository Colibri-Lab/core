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
 * Represents an exception that indicates an error in the application logic.
 *
 * @package Colibri\Exceptions
 */
class ApplicationErrorException extends \Exception
{

    /**
     * The error code for the application error.
     */
    public const ErrorCode = 500;

    /**
     * Error message for validation errors in the application.
     */
    public const ValidationError = 'Application validation error';

    /**
     * General application error message.
     */
    public const ApplicationError = 'Application Error';
}
