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
 * Утилиты для работы с числами
 */
class NumericHelper
{

    /**
     * В денежном виде
     *
     * @param number $number
     * @return string
     * @testFunction testNumericHelperToMoney
     */
    public static function ToMoney($number)
    {
        return NumericHelper::Format($number, '.', 2, true, '&nbsp;');
    }


    /**
     * В денежном виде
     *
     * @param number $number
     * @return string
     * @testFunction testNumericHelperFormat
     */
    public static function Format($number, $decPoint = '.', $deccount = 2, $removeLeadingZeroes = false, $thousandsSep = '')
    {

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
     * Проставить пробелы тысячами
     *
     * @param number $price
     * @return string
     * @testFunction testNumericHelperHumanize
     * @deprecated
     */
    public static function Humanize($price)
    {
        return $price;
    }
}
