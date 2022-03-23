<?php

namespace Colibri\Data\Storages\Fields;

use Colibri\Utils\Debug;
use DateTime;
use JsonSerializable;

/**
 * Класс для работы с полями типа datatime
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class DateTimeField extends DateTime implements JsonSerializable {

    static $defaultLocale = null;

    public function __construct($data, $storage = null, $field = null) {
        parent::__construct($data);
    }

    /**
     * Return Date in ISO8601 format
     *
     * @return String
     */
    public function __toString() {
        return $this->format('yyyy-MM-dd HH:mm:ss');
    }

    /**
     * Return difference between $this and $now
     *
     * @param DateTime|string $now
     * @return \DateInterval
     */
    public function diff($object, $absolute = NULL) {
        if(!($object instanceOf DateTime)) {
            $object = new DateTime($object);
        }
        return parent::diff($object);
    }

    /**
     * Return Age in Years
     *
     * @param \Datetime|String $now
     * @return Integer
     */
    public function getAge($now = 'NOW') {
        return (int)$this->diff($now)->format('%y');
    }    

    public function format($format, $locale = null)
    {

        $loc = ($locale ?: static::$defaultLocale);

        if(class_exists('\IntlDateFormatter') && $loc) {
            $intlFormatter = new \IntlDateFormatter($loc, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);  
            $intlFormatter->setPattern($format); 
            $result = $intlFormatter->format($this);
        }
        else {
            $result = parent::format($format);
        }

        if(\in_array($loc, ['RU_ru'])) {
            $result = str_replace([
                'янв.',
                'февр.',
                'мар.',
                'апр.',
                'июн.',
                'июл.',
                'авг.',
                'сент.',
                'окт.',
                'нояб.',
                'дек.',
            ], [
                'янв',
                'фев',
                'мар',
                'апр',
                'июн',
                'июл',
                'авг',
                'сен',
                'окт',
                'ноя',
                'дек',
            ], $result);
        }

        return $result;

    }

    public function jsonSerialize()
    {
        return (string)$this;
    }

}

