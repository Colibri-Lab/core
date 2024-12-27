<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

/**
 * Work with object and html strings
 * Functionality moved to class VariableHelper
 * @deprecated
 */
class ObjectHelper
{
    /**
     * @deprecated
     */
    public static function ArrayToObject(array $array): ?object
    {
        return VariableHelper::ArrayToObject($array);
    }

}
