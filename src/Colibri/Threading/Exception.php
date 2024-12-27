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
 */
class Exception extends AppException
{
    /**
     * Constructs an exception.
     *
     * @param int $code The error code from ErrorCodes.
     * @param string $message Additional error message text.
     */
    public function __construct(int $code, string $message)
    {
        parent::__construct(ErrorCodes::ToString($code) . ' ' . $message, $code);
    }
}
