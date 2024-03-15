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

}
