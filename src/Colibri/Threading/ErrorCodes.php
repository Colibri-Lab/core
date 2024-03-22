<?php

/**
 * Threading
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Threading
 */

namespace Colibri\Threading;

/**
 * Represents a list of error codes.
 */
class ErrorCodes
{
    /**
     * Error code for unknown property.
     */
    const UnknownProperty = 1;

    /**
     * Returns the textual representation of an error based on its code.
     *
     * @param int $code The error code.
     * @return string|null The textual representation of the error.
     */
    public static function ToString(int $code): ?string
    {
        if ($code == ErrorCodes::UnknownProperty) {
            return 'Unknown property';
        }
        return null;
    }
}