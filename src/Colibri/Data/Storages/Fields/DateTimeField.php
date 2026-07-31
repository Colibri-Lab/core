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
 * @extends DateTime
 * @implements JsonSerializable
 */
class DateTimeField extends DateTime implements JsonSerializable
{
    /**
     * Default locale for date formatting.
     *
     * @var string|null
     */
    public static $defaultLocale = null;

    /**
     * Constructs a new DateTimeField instance.
     *
     * @param mixed $data The date/time data to initialize the field with.
     * @param Storage|null $storage The associated storage (optional).
     * @param Field|null $field The associated field (optional).
     */
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
     * @param DateTimeInterface|string $object The date/time to compare with.
     * @param bool|null $absolute Whether to return the absolute difference.
     * @return DateInterval
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
     * @param DateTimeInterface|string $now
     * @return int
     */
    public function getAge(DateTime|string $now = 'NOW'): int
    {
        return (int) $this->diff($now)->format('%y');
    }

    /**
     * Formats the date/time according to the specified format and locale.
     *
     * @param string $format The format string.
     * @param string|null $locale The locale to use for formatting (optional).
     * @return string The formatted date/time string.
     */
    public function format(string $format, ?string $locale = null): string
    {

        $loc = ($locale ?: static::$defaultLocale ?: App::$systemLocale ?: 'en_US');

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

    /**
     * Returns the date/time as a string in ISO8601 format.
     *
     * @return string The date/time in ISO8601 format.
     */
    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    /**
     * Returns the parameter type name for this date/time field.
     *
     * @return string The parameter type name.
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }

    /**
     * Returns null value for the field.
     *
     * @return mixed Always returns null.
     */
    public static function null(): mixed
    {
        return null;
    }

}
