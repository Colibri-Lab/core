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
 * @class
 */
class ObjectHelper
{
    /**
     * Converts an array to an object.
     * This function is deprecated and will be removed in future versions. Use the VariableHelper::ArrayToObject method instead.
     * @deprecated
     * @public
     * @static
     */
    public static function ArrayToObject(array $array): ?object
    {
        return VariableHelper::ArrayToObject($array);
    }


}
