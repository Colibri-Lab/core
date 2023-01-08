<?php

/**
 * Обьект в html и обратно
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 * @version 1.0.0
 * 
 */

namespace Colibri\Common;

/**
 * Обьект в html и обратно
 * @deprecated
 */
class ObjectHelper
{

    /**
     * Превращает все ассоциативные массивы в обьекты
     * @param array $array массив данных
     * @return object|null
     * @testFunction testObjectHelperArrayToObject
     * @deprecated
     */
    public static function ArrayToObject(array $array): ?object
    {
        return VariableHelper::ArrayToObject($array);
    }

}