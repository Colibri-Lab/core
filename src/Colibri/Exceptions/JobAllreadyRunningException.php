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
 *
 * @package Colibri\Exceptions
 */
class JobAllreadyRunningException extends \Exception
{

    /**
     * The error code for the application error.
     */
    public const Code = 500;

    /**
     * General application error message.
     */
    public const Message = 'Job is allready running';
}
