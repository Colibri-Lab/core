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
 * @class
 * @extends \Exception
 */
class ApplicationErrorException extends \Exception
{
    /**
     * The error code for the application error.
     * @const int
     * @public
     */
    public const ErrorCode = 500;

    /**
     * Error message for validation errors in the application.
     * @const string
     * @public
     */
    public const ValidationError = 'Application validation error';

    /**
     * General application error message.
     * @const string
     * @public
     */
    public const ApplicationError = 'Application Error';
}
