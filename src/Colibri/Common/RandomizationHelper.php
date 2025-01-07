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
 */
class RandomizationHelper
{
    /**
     * Generates a random seed value.
     *
     * @return int A randomly generated seed.
     */
    public static function Seed(): int
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }

    /**
     * Generates a random integer within the specified range.
     *
     * @param int|float $min The minimum value (inclusive).
     * @param int|float $max The maximum value (inclusive).
     *
     * @return int A randomly generated integer between $min and $max.
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
