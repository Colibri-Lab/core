<?php

namespace Colibri\Data\Storages\Fields;

/**
 * Класс для работы с полями типа datatime
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class DateField extends DateTimeField
{

    /**
     * Return Date in ISO8601 format
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->format('yyyy-MM-dd');
    }

    public static function ParamTypeName(): string
    {
        return 'string';
    } 

    public static function Null(): mixed
    {
        return null;
    }

}