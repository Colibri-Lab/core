<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 */

namespace Colibri\Common;
use Colibri\Utils\Debug;

/**
 * Класс обертка над датой
 * @testFunction testDateHelper
 */
class DateHelper
{

    /** Количество секунд в году */
    const YEAR  = 31556926;
    /** Количество секунд в месяце */
    const MONTH = 2629744;
    /** Количество секунд в неделю */
    const WEEK  = 604800;
    /** Количество секунд в дне */
    const DAY   = 86400;
    /** Количество секунд в час */
    const HOUR  = 3600;
    /** Количество секунд в минуту */
    const MINUTE = 60;

    public static function Create(int $year, int $month, int $day) 
    {
        return mktime(0, 0, 0, $month, $day, $year);
    }

    public static function LastDayOfMonth(int|null $date): bool|int
    {
        return strtotime('last day of this month', $date);
    }

    /**
     * Вывести в формате RFC
     *
     * @param integer $time
     * @return string
     * @testFunction testDateHelperRFC
     */
    public static function RFC(int $time = null): string
    {
        $tz = date('Z');
        $tzs = ($tz < 0) ? '-' : '+';
        $tz = abs($tz);
        $tz = (int)($tz / 3600) * 100 + ($tz % 3600) / 60;
        return sprintf("%s %s%04d", date('D, j M Y H:i:s', VariableHelper::IsNull($time) ? time() : $time), $tzs, $tz);
    }

    /**
     * Вернуть в формате для базы данных
     *
     * @param int $time
     * @param string $format
     * @return string
     * @testFunction testDateHelperToDbString
     */
    public static function ToDbString(?int $time = null, ?string $format = '%Y-%m-%d %H:%M:%S'): string
    {
        if (VariableHelper::IsNull($time)) {
            $time = time();
        } else {
            $time = (VariableHelper::IsNumeric($time) ? $time : strtotime($time));
        }
        return TimeZoneHelper::FTimeU($format, $time);
    }

    /**
     * Вернуть дату в читабельном виде
     *
     * @param int $time
     * @param boolean $showTime
     * @return string
     * @testFunction testDateHelperToHumanDate
     */
    public static function ToHumanDate(?int $time = null, ?bool $showTime = false): string
    {
        if (is_null($time)) {
            $time = time();
        }
        return ((int)strftime('%d', $time)) . ' ' . TimeZoneHelper::Month2(strftime('%m', $time) - 1) . ' ' . strftime('%Y', $time) . ($showTime ? ' ' . strftime('%H', $time) . ':' . strftime('%M', $time) : '');
    }

    /**
     * Строка в цифру
     *
     * @param string $datestring
     * @return integer|null
     * @testFunction testDateHelperToUnixTime
     */
    public static function ToUnixTime(string $datestring): int|null
    {
        return strtotime($datestring);
    }

    /**
     * Количество лет между датами
     *
     * @param integer $time
     * @return string
     * @testFunction testDateHelperAge
     */
    public static function Age(int $time): string
    {
        $time = time() - $time; // to get the time since that moment

        $tokens = array(
            31536000 => array('год', 'года', 'лет'),
            2592000 => array('месяц', 'месяца', 'месяцев'),
            604800 => array('неделю', 'недели', 'недель'),
            86400 => array('день', 'дня', 'дней'),
            3600 => array('час', 'часа', 'часов'),
            60 => array('минуту', 'минуты', 'минут'),
            1 => array('секунду', 'секунды', 'секунд')
        );

        foreach ($tokens as $unit => $labels) {
            if ($time < $unit) {
                continue;
            }
            $numberOfUnits = floor($time / $unit);
            $ret = ($numberOfUnits > 1 ? $numberOfUnits . ' ' : '') . StringHelper::FormatSequence($numberOfUnits, $labels) . ' назад';
            if ($ret == 'день назад') {
                $ret = 'вчера';
            }
            return $ret;
        }

        return 'только что';
    }

    /**
     * Количество лет между датами (зачем это ? не знаю)
     *
     * @param integer $time
     * @return integer|false
     * @testFunction testDateHelperAgeYears
     */
    public static function AgeYears(int|string $time): string
    {

        if (VariableHelper::IsString($time)) {
            $time = strtotime($time);
        }

        if (!$time) {
            return false;
        }

        $day = date('j', $time);
        $month = date('n', $time);
        $year = date('Y', $time);

        $age = date('Y') - $year;
        $m = date('n') - $month;
        if ($m < 0 || ($m === 0 && date('j') < $day)) {
            $age--;
        }

        return $age;
    }

    /**
     * Количество секунд в время HH:MM:SS
     *
     * @param integer $number
     * @testFunction testDateHelperTimeToString
     */
    public static function TimeToString(int $number): ?string
    {

        if (!VariableHelper::IsNumeric($number) || $number < 0) {
            return null;
        }

        $hours = 0;
        $mins = 0;
        $secs = 0;

        if ($number >= 60) {
            $secs = $number % 60;
            $number = (int)($number / 60);
            if ($number >= 60) {
                $mins = $number % 60;
                $number = (int)($number / 60);
                if ($number >= 60) {
                    $hours = $number % 60;
                    $number = (int)($number / 60);
                } else {
                    $hours = $number;
                }
            } else {
                $mins = $number;
            }
        } else {
            $secs = $number;
        }

        $txt = "";
        $txt .= StringHelper::Expand($hours, 2, "0") . ":";
        $txt .= StringHelper::Expand($mins, 2, "0") . ":";
        $txt .= StringHelper::Expand($secs, 2, "0") . ":";

        $txt = ltrim($txt, "0");
        $txt = ltrim($txt, ":");

        return substr($txt, 0, strlen($txt) - 1);
    }

    /**
     * Рассчитывает разницу в полных годах в полных месяцах и полных днях между датами
     *
     * @param integer $time1 дата начала отсчета
     * @param integer $time2 дата окончания отсчета
     * @return object
     * @testFunction testDateHelperDiff
     */
    static function Diff(int $time1, int $time2): string
    {

        try {

            // не считаем дату начала и считаем дату окончания полностью
            $time1 = strftime('%Y-%m-%d 00:00:00', $time1);
            $time1 = strtotime('+1 days', strtotime($time1));
            $time2 = strftime('%Y-%m-%d 00:00:00', $time2);
            $time2 = strtotime('+1 days', strtotime($time2));

            // считаем разницу в полных годах
            $time1 = strftime('%Y-%m-%d 00:00:00', $time1);
            $time2 = strftime('%Y-%m-%d 00:00:00', $time2);
            $time1c = date_create($time1);
            $time2c = date_create($time2);
            $diff = date_diff($time2c, $time1c, false);
            $y = $diff->y;

            // двигаем дату начала на нужно количество лет вперед
            $time1 = strtotime('+' . $y . ' years', strtotime($time1));
            $time2 = strtotime($time2);

            // считаем разницу в полных месяцах
            $time1 = strftime('%Y-%m-%d 00:00:00', $time1);
            $time2 = strftime('%Y-%m-%d 00:00:00', $time2);

            $time1c = date_create($time1);
            $time2c = date_create($time2);
            $diff = date_diff($time2c, $time1c, false);
            $m = $diff->m;

            // двигаем дату начала на нужное количество вперед
            $time1 = strtotime('+' . $m . ' month', strtotime($time1));
            $time2 = strtotime($time2);

            $time1 = strftime('%Y-%m-%d 00:00:00', $time1);
            $time2 = strftime('%Y-%m-%d 00:00:00', $time2);

            // считаем количество полных дней
            $time1c = date_create($time1);
            $time2c = date_create($time2);
            $diff = date_diff($time2c, $time1c, false);
            $d = $diff->d;
        } catch (\Exception $e) {
            $y = 0;
            $m = 0;
            $d = 0;
        }

        return (object)['years' => $y, 'months' => $m, 'days' => $d];
    }

    static function FromDDMMYYYY(string $dateString, string $delimiter = '.', $format = '%Y-%m-%d %H:%M:%S'): string
    {
        if(strstr($dateString, ' ') !== false) {
            $dateString = explode(' ', $dateString);
            $dateString = $dateString[0];
        }

        $parts = explode($delimiter, $dateString);
        $time = self::Create($parts[2] ?? 0, $parts[1] ?? 0, $parts[0] ?? 0);
        return self::ToDbString($time, $format);
    }
}
