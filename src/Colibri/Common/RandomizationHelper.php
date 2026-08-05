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
 * Randomization helper
 * @class
 */
class RandomizationHelper
{
    /**
     * Generates a random seed value.
     *
     * @return int A randomly generated seed.
     * @public
     * @static
     * @example
     * ```
     * $seed = RandomizationHelper::Seed();
     * echo $seed; // Outputs a random seed value.
     * ```
     */
    public static function Seed(): int
    {
        list($usec, $sec) = explode(' ', microtime());
        return (int) ((float) $sec + ((float) $usec * 100000));
    }

    /**
     * Generates a random integer within the specified range.
     *
     * @param int|float $min The minimum value (inclusive).
     * @param int|float $max The maximum value (inclusive).
     *
     * @return int A randomly generated integer between $min and $max.
     * @public
     * @static
     * @example
     * ```
     * $min = 1;
     * $max = 10;
     * $randomInt = RandomizationHelper::Integer($min, $max);
     * echo $randomInt; // Outputs a random integer between 1 and 10.
     * ```
     */
    public static function Integer(int|float $min, int|float $max): int
    {
        return rand((int)$min, (int)$max);
    }

    /**
     * Returns a randomly generated string of mixed characters with the specified length.
     *
     * @param int $length The desired length of the random string.
     *
     * @return string The randomly generated mixed string.
     * @public
     * @static
     * @example
     * ```
     * $length = 8;
     * $randomString = RandomizationHelper::Mixed($length);
     * echo $randomString; // Outputs a random string of 8 mixed characters.
     * ```
     */
    public static function Mixed(int $length): string
    {
        $j = 0;
        $tmp = "";
        $c = array();
        $i = 0;

        for ($j = 1; $j <= $length; $j++) {
            $i = (int) RandomizationHelper::Integer(0, 2.999999);
            $c[0] = chr((int) RandomizationHelper::Integer(ord("A"), ord("Z")));
            $c[1] = chr((int) RandomizationHelper::Integer(ord("a"), ord("z")));
            $c[2] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $tmp = $tmp . $c[$i];
        }

        return $tmp;
    }

    /**
     * Generates a random string of the specified length, consisting of numeric digits.
     *
     * @param int $length The desired length of the random string.
     *
     * @return string The randomly generated string containing numeric digits.
     * @testFunction testRandomizationHelperNumeric
     * @public
     * @static
     * @example
     * ```
     * $length = 6;
     * $randomNumericString = RandomizationHelper::Numeric($length);
     * echo $randomNumericString; // Outputs a random string of 6 numeric digits.
     * ```
     */
    public static function Numeric(int $length): string
    {
        $j = 0;
        $tmp = "";
        $c = array();
        $i = 0;

        for ($j = 1; $j <= $length; $j++) {
            $i = (int) RandomizationHelper::Integer(0, 2.999999);
            $c[0] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $c[1] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $c[2] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $tmp = $tmp . $c[$i];
        }

        return $tmp;
    }

    /**
     * Generates a random string of characters with the specified length.
     *
     * @param int $length The desired length of the random string.
     *
     * @return string The randomly generated string of characters.
     * @public
     * @static
     * @example
     * ```
     * $length = 10;
     * $randomCharacterString = RandomizationHelper::Character($length);
     * echo $randomCharacterString; // Outputs a random string of 10 characters.
     * ```
     */
    public static function Character(int $length): string
    {
        $tmp = "";
        $c = array();

        for ($i = 0; $i < $length; $i++) {
            $j = (int) rand(0, 1);
            $c[0] = chr((int) RandomizationHelper::Integer(ord("A"), ord("Z")));
            $c[1] = chr((int) RandomizationHelper::Integer(ord("a"), ord("z")));
            $tmp = $tmp . $c[$j];
        }

        return $tmp;
    }

}
