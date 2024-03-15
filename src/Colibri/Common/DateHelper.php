<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */
namespace Colibri\Common;

use Colibri\Utils\Debug;
use DateTime;
use DateTimeZone;
use Colibri\Data\Storages\Fields\DateTimeField;

/**
 * Helper class for working with dates.
 */
class DateHelper
{
    private const NBSP = '&nbsp;';
    
    private const DATEFORMAT = 'Y-m-d 00:00:00';

    /** Seconds in year */
    public const YEAR = 31556926;
    
    /** Seconds in month */
    public const MONTH = 2629744;
    
    /** Seconds in week */
    public const WEEK = 604800;
    
    /** Seconds in day */
    public const DAY = 86400;
    
    /** Seconds in hour */
    public const HOUR = 3600;

    /** Seconds in minute */
    public const MINUTE = 60;

    /**
     * Creates a date object based on the provided year, month, and day.
     * 
     * ```
     * DateHelper::Create(2024,1,1) returns 1704067200
     * ```
     *
     * @param int $year The year (e.g., 2024).
     * @param int $month The month (1 to 12).
     * @param int $day The day of the month (1 to 31).
     * @return int 
     */
    public static function Create(int $year, int $month, int $day): bool|int
    {
        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * Calculates the last day of the month for the given date.
     * 
     * ```
     * DateHelper::LastDayOfMonth(1704067200) returns 31
     * DateHelper::LastDayOfMonth() returns last day of current month, if january = 31, february = 28 or 29
     * ```
     *
     * @param int|null $date The date (as an integer timestamp) or null for the current date.
     * @return bool|int The last day of the month (as an integer day of the month), or false if invalid input.
     */
    public static function LastDayOfMonth(?int $date = null): bool|int
    {
        return strtotime('last day of this month', $date);
    }

    /**
     * Generates an RFC 2822 formatted date string based on the provided timestamp.
     * 
     * ```
     * DateHelper::RFC() today returns Fri, 15 Mar 2024 03:57:55 +0000
     * ```
     *
     * @param float|null $time The timestamp (as a float) or null for the current time.
     * @return string The RFC 2822 formatted date string.
     */
    public static function RFC(?float $time = null): string
    {
        $tz = date('Z');
        $tzs = ($tz < 0) ? '-' : '+';
        $tz = abs($tz);
        $tz = (int) ($tz / 3600) * 100 + ($tz % 3600) / 60;
        return sprintf("%s %s%04d", date('D, j M Y H:i:s', VariableHelper::IsNull($time) ? time() : $time), $tzs, $tz);
    }

    /**
     * Converts a timestamp or date string to a database-friendly formatted string.
     * 
     * ```
     * DateHelper::ToDbString(1704067200) returns 2024-01-01 00:00:00
     * ```
     *
     * @param float|string|null $time The timestamp (as a float), date string, or null for the current time.
     * @param string|null $format The desired format (default is 'Y-m-d H:i:s').
     * @return string The formatted date string suitable for database storage.
     */
    public static function ToDbString(float|string|null $time = null, ?string $format = 'Y-m-d H:i:s'): string
    {
        if (VariableHelper::IsNull($time)) {
            $time = time();
        } else {
            $time = (VariableHelper::IsNumeric($time) ? $time : strtotime($time));
        }
        return TimeZoneHelper::FTimeU($format, $time);
    }

    /**
     * Converts a timestamp to a human-readable date string.
     * 
     * ```
     * DateHelper::ToHumanDate() returns today 15 марта 2024
     * ```
     *
     * @param float|null $time The timestamp (as a float) or null for the current time.
     * @param bool $showTime Whether to include the time portion in the output (default is false).
     * @return string The human-readable date string.
     */
    public static function ToHumanDate(?float $time = null, ?bool $showTime = false): string
    {
        if (is_null($time)) {
            $time = time();
        }
        return ((int) date('d', $time)) . ' ' .
            TimeZoneHelper::Month2(date('m', $time) - 1) . ' ' .
            date('Y', $time) .
            ($showTime ? ' ' . date('H', $time) . ':' . date('i', $time) : '');
    }

    /**
     * Converts a timestamp or date string to a human-readable quarter representation.
     * 
     * ```
     * DateHelper::ToQuarter() returns today 1 квартал 2024
     * DateHelper::ToQuarter(null, '', true) returns today 1
     * DateHelper::ToQuarter('2024-08-01') returns today 3 квартал 2024
     * ```
     *
     * @param float|string|null $time The timestamp (as a float), date string, or null for the current time.
     * @param string $quarterName The name to use for the quarter (e.g., 'quarter' or 'Q').
     * @param bool $numberOnly Whether to include only the quarter number (default is false).
     * @return string The human-readable quarter representation (e.g., 'Q1 2024' or '1st quarter 2024').
     */
    public static function ToQuarter(
        int|string|null $time = null,
        string $quarterName = 'квартал',
        bool $numberOnly = false
    ): string {

        if($time === null) {
            $time = time();
        }

        if(is_string($time)) {
            $time = strtotime($time);
        }

        $kv = (int)((date('n', $time)-1)/3+1);
        if ($numberOnly) {
            return $kv;
        }

        $year = date('Y', $time);

        return $kv.' '.$quarterName.' '.$year;
    }

    /**
     * Converts a date string to a Unix timestamp.
     * 
     * ```
     * DateHelper::ToUnixTime('2024-01-01') returns 1704067200
     * ```
     *
     * @param string $datestring The date string to convert.
     * @return int|null The Unix timestamp corresponding to the date string, or null if invalid input.
     */
    public static function ToUnixTime(string $datestring): int|null
    {
        return strtotime($datestring);
    }

    /**
     * Calculates the age based on the provided timestamp.
     * 
     * ```
     * DateHelper::Age(1704067200) returns 2 месяца назад
     * ```
     *
     * @param int $time The timestamp (as an integer) representing the date.
     * @return string The age in years as a string (e.g., "30 years old").
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
            $ret = ($numberOfUnits > 1 ? $numberOfUnits . ' ' : '') .
                StringHelper::FormatSequence($numberOfUnits, $labels) . ' назад';
            if ($ret == 'день назад') {
                $ret = 'вчера';
            }
            return $ret;
        }

        return 'только что';
    }

    /**
     * Calculates the age in years based on the provided timestamp or date string.
     * 
     * ```
     * DateHelper::AgeYears(1704067200) returns 0     
     * DateHelper::AgeYears(1602062200) returns 3   
     * ```
     *
     * @param int|string $time The timestamp (as an integer) or date string.
     * @return string The age in years as a string (e.g., "30 years old").
     */
    public static function AgeYears(int|string $time): int
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

        return (int)$age;
    }

    /**
     * Converts a numeric timestamp to a human-readable time string formated as HH:MM:SS
     * 
     * ```
     * DateHelper::TimeToString(1602062200) returns 57:16:40
     * ```
     *
     * @param int $number The timestamp (as an integer).
     * @return string|null The formatted time string (e.g., "12:30:10"), or null if invalid input.
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
            $number = (int) ($number / 60);
            if ($number >= 60) {
                $mins = $number % 60;
                $number = (int) ($number / 60);
                if ($number >= 60) {
                    $hours = $number % 60;
                    $number = (int) ($number / 60);
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
     * Calculates the difference between two timestamps.
     * 
     * ```
     * DateHelper::Diff(1602062200, 1704067200) returns (object)["years" => 3,"months" => 2,"days" => 25]
     * ```
     *
     * @param int $time1 The first timestamp.
     * @param int $time2 The second timestamp.
     * @return object An object representing the time difference (e.g., days, hours, minutes).
     */
    public static function Diff(int $time1, int $time2): object
    {

        try {

            // не считаем дату начала и считаем дату окончания полностью
            $time1 = date(self::DATEFORMAT, $time1);
            $time1 = strtotime('+1 days', strtotime($time1));
            $time2 = date(self::DATEFORMAT, $time2);
            $time2 = strtotime('+1 days', strtotime($time2));

            // считаем разницу в полных годах
            $time1 = date(self::DATEFORMAT, $time1);
            $time2 = date(self::DATEFORMAT, $time2);
            $time1c = date_create($time1);
            $time2c = date_create($time2);
            $diff = date_diff($time2c, $time1c, false);
            $y = $diff->y;

            // двигаем дату начала на нужно количество лет вперед
            $time1 = strtotime('+' . $y . ' years', strtotime($time1));
            $time2 = strtotime($time2);

            // считаем разницу в полных месяцах
            $time1 = date(self::DATEFORMAT, $time1);
            $time2 = date(self::DATEFORMAT, $time2);

            $time1c = date_create($time1);
            $time2c = date_create($time2);
            $diff = date_diff($time2c, $time1c, false);
            $m = $diff->m;

            // двигаем дату начала на нужное количество вперед
            $time1 = strtotime('+' . $m . ' month', strtotime($time1));
            $time2 = strtotime($time2);

            $time1 = date(self::DATEFORMAT, $time1);
            $time2 = date(self::DATEFORMAT, $time2);

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

        return (object) ['years' => $y, 'months' => $m, 'days' => $d];
    }

    /**
     * Calculates the difference between two timestamps in terms of full tokens (years, months, and days).
     * 
     * ```
     * DateHelper::DiffFullTokens(1602062200, 1704067200) returns "3 года 2 месяца25 дней"
     * ```
     *
     * @param int $time1 The first timestamp.
     * @param int $time2 The second timestamp.
     * @param string $splitter The delimiter to use between tokens (default is a space).
     * @param array $tokens An array of token names for years, months, and days (e.g., [['год', 'года', 'лет'], ...]).
     * @return string The formatted difference string (e.g., "2 года 3 месяца 15 дней").
     */
    public static function DiffFullTokens(
        $time1,
        $time2,
        $splitter = ' ',
        $tokens = [
            ['год', 'года', 'лет'],
            ['месяц', 'месяца', 'месяцев'],
            ['день', 'дня', 'дней']
        ]
    ): string {

        $diff = self::Diff($time1, $time2);
        return
            trim(($diff->years > 0 ? str_replace(
                ' ',
                self::NBSP,
                StringHelper::FormatSequence($diff->years, $tokens[0], true)
            ).$splitter : '')
            .trim($diff->months > 0 ? str_replace(
                ' ',
                self::NBSP,
                StringHelper::FormatSequence($diff->months, $tokens[1], true)
            ).$splitter : '')
            .trim($diff->days > 0 ? str_replace(
                ' ',
                self::NBSP,
                StringHelper::FormatSequence($diff->days, $tokens[2], true)
            ) : ''), $splitter);

    }

    /**
     * Converts a date string in the format "DD.MM.YYYY" to a correctly formatted string.
     * 
     * ```
     * DateHelper::FromDDMMYYYY('01.01.2024') returns 2024-01-01
     * ```
     *
     * @param string $dateString The date string in "DD.MM.YYYY" format.
     * @param string $delimiter The delimiter used in the input date string (default is '.').
     * @param string $format The desired output format (default is 'Y-m-d H:i:s').
     * @return string The formatted date string.
     */
    public static function FromDDMMYYYY(string $dateString, string $delimiter = '.', $format = 'Y-m-d H:i:s'): string
    {
        if (strstr($dateString, ' ') !== false) {
            $dateString = explode(' ', $dateString);
            $dateString = $dateString[0];
        }

        $parts = $dateString ? explode($delimiter, $dateString) : [];
        $time = self::Create($parts[2] ?? 0, $parts[1] ?? 0, $parts[0] ?? 0);
        return self::ToDbString($time, $format);
    }

    /**
     * Converts a JavaScript-style date string to a `DateTimeField` object.
     *
     * @param string $date The JavaScript-style date string (e.g., "2024-03-15T12:30:45").
     * @return DateTimeField A `DateTimeField` object representing the parsed date and time.
     */
    public static function FromJSDate(string $date): DateTimeField
    {
        $date = explode('-', $date);
        $zone = $date[1];
        $date = (int) ($date[0] / 1000);
        $dt = new DateTimeField('now');
        $dt->setTimestamp($date);
        $dt->setTimezone(new DateTimeZone('-' . $zone));
        return $dt;
    }

    /**
     * Calculates the number of days in the month for the given DateTime object.
     * 
     * ```
     * DateHelper::DaysInMonth(new DateTime('now')) today returns 31
     * ```
     *
     * @param DateTime $dt The DateTime object representing the desired month.
     * @return int The number of days in the month (28 to 31).
     */
    public static function DaysInMonth(DateTime $dt): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $dt->format('M'), $dt->format('yyyy'));
    }

}
