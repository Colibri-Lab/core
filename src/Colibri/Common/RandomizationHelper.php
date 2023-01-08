<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Helpers
 */

namespace Colibri\Common;

/**
 * Всякие разные виды рандомизации
 */
class RandomizationHelper
{

    /**
     * Вернуть новый SEED
     *
     * @return integer
     * @testFunction testRandomizationHelperSeed
     */
    public static function Seed(): int
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }

    /**
     * Рандомное значение между макс и мин
     *
     * @param integer $min
     * @param integer $max
     * @return integer
     * @testFunction testRandomizationHelperInteger
     */
    public static function Integer(int $min, int $max): int
    {
        return rand($min, $max);
    }

    /**
     * Указанное количество рандомных символов
     *
     * @param integer $l
     * @return string
     * @testFunction testRandomizationHelperMixed
     */
    public static function Mixed(int $l): string
    {
        $j = 0;
        $tmp = "";
        $c = array();
        $i = 0;

        for ($j = 1; $j <= $l; $j++) {
            $i = (int) RandomizationHelper::Integer(0, 2.999999);
            $c[0] = chr((int) RandomizationHelper::Integer(ord("A"), ord("Z")));
            $c[1] = chr((int) RandomizationHelper::Integer(ord("a"), ord("z")));
            $c[2] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $tmp = $tmp . $c[$i];
        }

        return $tmp;
    }

    /**
     * Указанное количество произвольных цифр
     *
     * @param integer $l
     * @return string
     * @testFunction testRandomizationHelperNumeric
     */
    public static function Numeric(int $l): string
    {
        $j = 0;
        $tmp = "";
        $c = array();
        $i = 0;

        for ($j = 1; $j <= $l; $j++) {
            $i = (int) RandomizationHelper::Integer(0, 2.999999);
            $c[0] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $c[1] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $c[2] = chr((int) RandomizationHelper::Integer(ord("0"), ord("9")));
            $tmp = $tmp . $c[$i];
        }

        return $tmp;
    }

    /**
     * Указанное количество рандомных символов - без цифр
     *
     * @param integer $l
     * @return string
     * @testFunction testRandomizationHelperCharacter
     */
    public static function Character(int $l): string
    {
        $tmp = "";
        $c = array();

        for ($i = 0; $i < $l; $i++) {
            $j = (int) rand(0, 1);
            $c[0] = chr((int) RandomizationHelper::Integer(ord("A"), ord("Z")));
            $c[1] = chr((int) RandomizationHelper::Integer(ord("a"), ord("z")));
            $tmp = $tmp . $c[$j];
        }

        return $tmp;
    }
}