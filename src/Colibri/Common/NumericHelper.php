<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

use Exception;

/**
 * Utility class for work with numbers
 */
class NumericHelper
{
    /**
     * Converts a numeric value to a formatted money string.
     *
     * @param float $number The numeric value to convert.
     *
     * @return string The money representation of the input value.
     */
    public static function ToMoney(float $number): string
    {
        return NumericHelper::Format($number, '.', 2, true, '&nbsp;');
    }


    /**
     * Formats a numeric value as a string with customizable decimal and thousands separators.
     *
     * @param float $number The numeric value to format.
     * @param string $decPoint The decimal point character (optional, default is '.').
     * @param int $deccount The number of decimal places (optional, default is 2).
     * @param bool $removeLeadingZeroes Whether to remove leading zeroes (optional, default is false).
     * @param string $thousandsSep The thousands separator (optional, default is '').
     *
     * @return string The formatted numeric value as a string.
     */
    public static function Format(
        float $number,
        string $decPoint = '.',
        int $deccount = 2,
        bool $removeLeadingZeroes = false,
        string $thousandsSep = ''
    ): string {

        $zeroLeading = StringHelper::Expand('0', $deccount, '0');
        $removeLeading = $removeLeadingZeroes ? $decPoint . $zeroLeading : '';

        try {
            $return = number_format($number, $deccount, $decPoint, $thousandsSep);
        } catch (\Exception $e) {
            $return = '0.00';
        } catch (\Error $e) {
            $return = '0.00';
        }

        return str_replace($removeLeading, '', $return);
    }

    /**
     * @deprecated
     */
    public static function Humanize(float $price): string
    {
        return $price;
    }

    /**
     * Normalizes a given input (string or other data) and returns a floating-point value.
     *
     * @param mixed $string The input data to be normalized.
     *
     * @return float The normalized value as a floating-point number.
     */
    public static function Normalize(mixed $string): float
    {

        if(!is_string($string)) {
            return $string;
        }

        $string = str_replace(' ', '', $string);
        $string = str_replace(',', '.', $string);

        return (float)$string;

    }

    public static function ToText($num, $unit = ['рубль','рубля','рублей']): string
    {
        if (!is_numeric($num) || !is_finite($num)) {
            throw new Exception("Input must be a finite number");
        }

        $units = [
            ["ноль"],
            ["один", "одна"],
            ["два", "две"],
            ["три"],
            ["четыре"],
            ["пять"],
            ["шесть"],
            ["семь"],
            ["восемь"],
            ["девять"]
        ];

        $teens = [
            "десять", "одиннадцать", "двенадцать", "тринадцать", "четырнадцать",
            "пятнадцать", "шестнадцать", "семнадцать", "восемнадцать", "девятнадцать"
        ];

        $tens = [
            "", "десять", "двадцать", "тридцать", "сорок",
            "пятьдесят", "шестьдесят", "семьдесят", "восемьдесят", "девяносто"
        ];

        $hundreds = [
            "", "сто", "двести", "триста", "четыреста",
            "пятьсот", "шестьсот", "семьсот", "восемьсот", "девятьсот"
        ];

        $thousandsForms = ["тысяча", "тысячи", "тысяч"];
        $millionsForms = ["миллион", "миллиона", "миллионов"];
        $billionsForms = ["миллиард", "миллиарда", "миллиардов"];
        $kopeksForms = ["копейка", "копейки", "копеек"];

        $getForm = function ($n, $forms) {
            $n = abs($n) % 100;
            if ($n >= 11 && $n <= 19) {
                return $forms[2];
            }
            $lastDigit = $n % 10;
            if ($lastDigit === 1) {
                return $forms[0];
            }
            if ($lastDigit >= 2 && $lastDigit <= 4) {
                return $forms[1];
            }
            return $forms[2];
        };

        $tripletToWords = function ($num, $female) use ($units, $teens, $tens, $hundreds) {
            $words = [];

            $h = intdiv($num, 100);
            $t = intdiv($num % 100, 10);
            $u = $num % 10;

            if ($h > 0) {
                $words[] = $hundreds[$h];
            }

            if ($t > 1) {
                $words[] = $tens[$t];
                if ($u > 0) {
                    $words[] = $units[$u][($female && $u <= 2) ? 1 : 0] ?? $units[$u][0];
                }
            } elseif ($t === 1) {
                $words[] = $teens[$u];
            } else {
                if ($u > 0 || empty($words)) {
                    $words[] = $units[$u][($female && $u <= 2) ? 1 : 0] ?? $units[$u][0];
                }
            }

            return implode(" ", $words);
        };

        // --- split integer and decimal ---
        $negative = $num < 0;
        $num = abs($num);

        $rub = (int)floor($num);
        $kop = (int)round(($num - $rub) * 100);

        if ($kop === 100) {
            $rub += 1;
            $kop = 0;
        }

        if ($rub === 0) {
            $result = ["ноль"];
        } else {
            $result = [];

            $billions = intdiv($rub, 1000000000);
            $millions = intdiv($rub % 1000000000, 1000000);
            $thousands = intdiv($rub % 1000000, 1000);
            $remainder = $rub % 1000;

            if ($billions > 0) {
                $result[] = $tripletToWords($billions, false);
                $result[] = $getForm($billions, $billionsForms);
            }

            if ($millions > 0) {
                $result[] = $tripletToWords($millions, false);
                $result[] = $getForm($millions, $millionsForms);
            }

            if ($thousands > 0) {
                $result[] = $tripletToWords($thousands, true);
                $result[] = $getForm($thousands, $thousandsForms);
            }

            if ($remainder > 0) {
                $result[] = $tripletToWords($remainder, false);
            }
        }

        $final = trim(preg_replace('/\s+/', ' ', implode(" ", $result)));

        // --- kopeks ---
        $kopText = str_pad($kop, 2, '0', STR_PAD_LEFT) . ' ' . $getForm($kop, $kopeksForms);

        if ($negative) {
            $final = "минус " . $final;
        }

        return $final . ' ' . $getForm($rub, $unit) . ' ' . $kopText;
    }

}
