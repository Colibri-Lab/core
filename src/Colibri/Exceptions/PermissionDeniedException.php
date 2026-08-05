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
 * Exception thrown to indicate that the server understood the request, but refuses to authorize it.
 * @class
 * @extends \Exception
 */
class PermissionDeniedException extends \Exception
{
    /**
     * The HTTP status code for permission denied.
     * @const int
     * @public
     */
    public const PermissionDeniedCode = 403;

    /**
     * Message indicating permission denied.
     * @const string
     * @public
     */
    public const PermissionDeniedMessage = 'Permission denied';

}
