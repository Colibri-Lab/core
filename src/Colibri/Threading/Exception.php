<?php

/**
 * Threading
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Threading
 */

namespace Colibri\Threading;

use Colibri\AppException;

/**
 * Exception for processes and threads.
 * @class
 * @extends AppException
 */
class Exception extends AppException
{
    /**
     * Constructs an exception.
     *
     * @param int $code The error code from ErrorCodes.
     * @param string $message Additional error message text.
     * @constructor
     * @public
     */
    public function __construct(int $code, string $message)
    {
        parent::__construct(ErrorCodes::ToString($code) . ' ' . $message, $code);
    }
}
