<?php

namespace Colibri\Data\Storages\Fields;

/**
 * Класс для работы с полями типа datatime
 * @class
 * @extends DateTimeField
 */
class DateField extends DateTimeField
{
    /**
     * Return Date in ISO8601 format
     *
     * @return string
     * @public
     * @magic
     */
    public function __toString(): string
    {
        return (string)$this->format('yyyy-MM-dd');
    }

    /**
     * Returns data type for the field
     *
     * @return string
     * @public
     * @static
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }

    /**
     * Returns null value for the field
     *
     * @return mixed Always returns null.
     * @public
     * @static
     */
    public static function null(): mixed
    {
        return null;
    }

}
