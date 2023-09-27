<?php

namespace Colibri\Exceptions;

class PermissionDeniedException extends \Exception
{
    public const PermissionDeniedCode = 403;
    public const PermissionDeniedMessage = 'Permission denied';

}
