<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 */

namespace Colibri\Common;

/**
 * Временная зона
 */
class TimeZoneHelper
{

    /**
     * Зона по умолчанию
     */
    public static $zone = 'ru';

    /**
     * Строки
     */
    public static $texts = array(
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
     * Установить зону глобально
     *
     * @param string $zone
     * @return bool
     * @testFunction testTimeZoneHelperSet
     */
    public static function Set($zone)
    {
        if (!isset(self::$texts[$zone])) {
            return false;
        }
        self::$zone = $zone;
        return true;
    }

    /**
     * Возвращает название месяца в текущей зоне (локализации)
     *
     * @param integer $month
     * @return string
     * @testFunction testTimeZoneHelperMonth
     */
    public static function Month($month)
    {
        return isset(self::$texts[self::$zone]['months'][$month]) ? self::$texts[self::$zone]['months'][$month] : null;
    }

    /**
     * Возвращает название месяца в текущей локализации в родительном падеже
     *
     * @param integer $month
     * @return string
     * @testFunction testTimeZoneHelperMonth2
     */
    public static function Month2($month)
    {
        return isset(self::$texts[self::$zone]['months2'][$month]) ? self::$texts[self::$zone]['months2'][$month] : null;
    }

    /**
     * Возвращает название недели в текущей локализации
     *
     * @param integer $weekday
     * @return string
     * @testFunction testTimeZoneHelperWeekday
     */
    public static function Weekday($weekday)
    {
        return isset(self::$texts[self::$zone]['weekdays'][$weekday]) ? self::$texts[self::$zone]['weekdays'][$weekday] : null;
    }

    /**
     * Форматирует строку с учетом зоны
     *
     * @param string $format
     * @param float $microtime
     * @return string
     * @testFunction testTimeZoneHelperFTimeU
     */
    public static function FTimeU($format, $microtime)
    {
        if (!is_numeric($microtime)) {
            return null;
        }
        if (preg_match('/^[0-9]*\\.([0-9]+)$/', $microtime, $reg)) {
            $decimal = substr(str_pad($reg[1], 6, "0"), 0, 6);
        } else {
            $decimal = "000000";
        }
        $format = preg_replace('/(%f)/', $decimal, $format);
        return strftime($format, $microtime);
    }
}
