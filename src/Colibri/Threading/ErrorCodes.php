<?php

/**
 * Threading
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Threading
 * 
 */

namespace Colibri\Threading;

/**
 * Список ошибок
 */
class ErrorCodes
{
    /** Неизвестная свойство */
    const UnknownProperty = 1;

    /**
     * Возвращает текстовое представление ошибки по коду
     *
     * @param int $code
     * @return string
     */
    public static function ToString(int $code): string
    {
        if ($code == ErrorCodes::UnknownProperty) {
            return 'Unknown property';
        }
        return null;
    }
}