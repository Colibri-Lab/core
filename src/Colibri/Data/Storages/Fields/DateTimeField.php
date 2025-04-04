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
class DateTimeField extends DateTime implements JsonSerializable
{
    public static $defaultLocale = null;

    public function __construct(mixed $data, ?Storage $storage = null, ?Field $field = null)
    {
        parent::__construct($data, new DateTimeZone(App::$systemTimezone));
    }

    /**
     * Return Date in ISO8601 format
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format('yyyy-MM-dd HH:mm:ss');
    }

    /**
     * Return difference between $this and $now
     *
     * @param DateTime|string $now
     * @return \DateInterval
     */
    public function diff(DateTimeInterface|string $object, bool $absolute = null): DateInterval
    {
        if (!($object instanceof DateTime)) {
            $object = new DateTime($object);
        }
        return parent::diff($object);
    }

    /**
     * Return Age in Years
     *
     * @param \DateTime|string $now
     * @return integer
     */
    public function getAge(DateTime|string $now = 'NOW'): int
    {
        return (int) $this->diff($now)->format('%y');
    }

    public function format(string $format, ?string $locale = null): string
    {

        $loc = ($locale ?: static::$defaultLocale);

        if (class_exists('\IntlDateFormatter') && $loc) {
            $intlFormatter = new \IntlDateFormatter($loc, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
            $intlFormatter->setPattern($format);
            $intlFormatter->setTimeZone($this->getTimezone());
            $result = $intlFormatter->format($this);
        } else {
            $result = parent::format($format);
        }

        if (\in_array($loc, ['RU_ru'])) {
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

    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    public static function ParamTypeName(): string
    {
        return 'string';
    }

    public static function null(): mixed
    {
        return null;
    }

}
