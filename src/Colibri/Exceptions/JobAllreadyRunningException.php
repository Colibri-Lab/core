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
 * Represents an exception that indicates that job is allready running
 * @class
 * @extends \Exception
 *
 * @package Colibri\Exceptions
 */
class JobAllreadyRunningException extends \Exception
{
    /**
     * The error code for the application error.
     * @const int
     * @public
     */
    public const Code = 500;

    /**
     * General application error message.
     * @const string
     * @public
     */
    public const Message = 'Job is allready running';
}
