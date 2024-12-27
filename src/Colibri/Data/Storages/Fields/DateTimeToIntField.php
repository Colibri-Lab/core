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
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class DateTimeToIntField extends DateTimeField
{
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

    public static function ParamTypeName(): string
    {
        return 'integer';
    }

    public static function null(): mixed
    {
        return 0;
    }

}
