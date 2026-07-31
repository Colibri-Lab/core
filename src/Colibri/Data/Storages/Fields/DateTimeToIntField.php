<?php

namespace Colibri\Data\Storages\Fields;

use Colibri\App;
use Colibri\Utils\Debug;
use DateTime;
use JsonSerializable;
use Colibri\Data\Storages\Storage;
use DateTimeInterface;
use DateInterval;
use DateTimeZone;

/**
 * Класс для работы с полями типа datatime
 * @class
 * @extends DateTimeField
 */
class DateTimeToIntField extends DateTimeField
{
    /**
     * Constructs a new DateTimeToIntField instance.
     *
     * @param mixed $data The date/time data to initialize the field with.
     * @param Storage|null $storage The associated storage (optional).
     * @param Field|null $field The associated field (optional).
     */
    public function __construct(mixed $data, ?Storage $storage = null, ?Field $field = null)
    {
        $dt = new DateTime();
        if(is_numeric($data)) {
            $dt->setTimestamp($data);
        }
        $data = $dt->format(DateTime::W3C);
        parent::__construct($data, $storage, $field);
    }

    /**
     * Return Date in ISO8601 format
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getTimestamp();
    }

    /**
     * Returns the parameter type name for this date/time field.
     *
     * @return string The parameter type name.
     */
    public static function ParamTypeName(): string
    {
        return 'integer';
    }

    /**
     * Returns null value for the field.
     *
     * @return mixed Always returns 0.
     */
    public static function null(): mixed
    {
        return 0;
    }

}
