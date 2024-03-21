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
 */
class PermissionDeniedException extends \Exception
{
    /**
     * The HTTP status code for permission denied.
     */
    public const PermissionDeniedCode = 403;

    /**
     * Message indicating permission denied.
     */
    public const PermissionDeniedMessage = 'Permission denied';

}
