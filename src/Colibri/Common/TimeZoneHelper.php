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
 * TimeZone helper
 * @class
 */
class TimeZoneHelper
{
    /**
     * TimeZone by default
     * @static
     * @public
     * @var string
     */
    public static string $zone = 'ru';

    /**
     * Strings
     * @static
     * @public
     * @var array
     */
    public static array $texts = array(
        'ru' => array(
            'months' => array('январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'),
            'months2' => array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'),
            'weekdays' => array('понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'),
        ),
        'en' => array(
            'months' => array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'dectember'),
            'months2' => array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'dectember'),
            'weekdays' => array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
        )
    );

    /**
     * Set timezone globaly
     *
     * @param string $zone
     * @return bool
     * @public
     * @static
     * @example
     * ```
     * TimeZoneHelper::Set('en');
     * echo TimeZoneHelper::$zone; // Outputs: en
     * ```
     */
    public static function Set(string $zone): bool
    {
        if (!isset(self::$texts[$zone])) {
            return false;
        }
        self::$zone = $zone;
        return true;
    }

    /**
     * Returns month name by month number
     *
     * @param integer $month
     * @return string
     * @public
     * @static
     * @example
     * ```
     * TimeZoneHelper::Set('ru');
     * $monthName = TimeZoneHelper::Month(1);
     * echo $monthName; // Outputs: январь
     * ```
     */
    public static function Month(int $month): ?string
    {
        return isset(self::$texts[self::$zone]['months'][$month]) ? self::$texts[self::$zone]['months'][$month] : null;
    }

    /**
     * Returns the name of the month in the current localization in the genitive case.
     *
     * @param integer $month
     * @return string
     * @public
     * @static
     * @example
     * ```
     * TimeZoneHelper::Set('ru');
     * $monthNameGenitive = TimeZoneHelper::Month2(1);
     * echo $monthNameGenitive; // Outputs: января
     * ```
     */
    public static function Month2(int $month): ?string
    {
        return isset(self::$texts[self::$zone]['months2'][$month]) ? self::$texts[self::$zone]['months2'][$month] : null;
    }

    /**
     * Returns the name of the week in the current localization
     *
     * @param integer $weekday
     * @return string
     * @public
     * @static
     * @example
     * ```
     * TimeZoneHelper::Set('ru');
     * $weekdayName = TimeZoneHelper::Weekday(1);
     * echo $weekdayName; // Outputs: понедельник
     * ```
     */
    public static function Weekday(int $weekday): ?string
    {
        return isset(self::$texts[self::$zone]['weekdays'][$weekday]) ? self::$texts[self::$zone]['weekdays'][$weekday] : null;
    }

    /**
     * Formats a string based on the zone
     *
     * @param string $format
     * @param float $microtime
     * @return string
     * @public
     * @static
     * @example
     * ```
     * TimeZoneHelper::Set('ru');
     * $formattedTime = TimeZoneHelper::FTimeU('Y-m-d H:i:s', microtime(true));
     * echo $formattedTime; // Outputs the current date and time in the format "Y-m-d H:i:s"
     * ```
     */
    public static function FTimeU(string $format, float $microtime): ?string
    {
        if (!is_numeric($microtime)) {
            return null;
        }
        if (preg_match('/^[0-9]*\\.([0-9]+)$/', $microtime, $reg)) {
            $decimal = substr(str_pad($reg[1], 6, "0"), 0, 6);
        } else {
            $decimal = "000000";
        }
        $format = preg_replace('/(f)/', $decimal, $format);
        return date($format, (int)$microtime);
    }

}
