<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

/**
 * Represents an encoding utility class.
 */
class Encoding
{
    /** UTF8 encoding */
    public const UTF8 = "utf-8";

    /** Windows 1251 encoding */
    public const CP1251 = "windows-1251";

    /** ISO 8859-1 encoding */
    public const ISO_8859_1 = 'iso-8859-1';

    /**
     * Converts a string, array, or object from one encoding to another.
     *
     * ```
     * Enconding::Convert('халлоу', Encoding::CP1251) returns 'халлоу' in windows-1251 encoding
     * Enconding::Convert(['халлоу','hello'], Encoding::CP1251) returns ['халлоу','hello'] in windows-1251 encoding
     * ```
     *
     * @param string|array|object $string The input data to be converted.
     * @param string $to The target format.
     * @param string|null $from The source format (optional).
     *
     * @return string|array|object The converted data.
     */
    public static function Convert(
        string|array|object $string,
        string $to,
        ?string $from = null
    ): string|array|object {
        if (is_array($string) || is_object($string)) {

            $isObject = is_object($string);
            $return = [];
            $string = (array) $string;
            foreach ($string as $key => $value) {
                $return[Encoding::Convert($key, $to, $from)] = Encoding::Convert($value, $to, $from);
            }

            if ($isObject) {
                $return = (object) $return;
            }

            return $return;

        } elseif (!is_string($string)) {
            return $string;
        }

        if (!$from) {
            $from = Encoding::Detect($string);
        }

        $to = strtolower($to);
        $from = strtolower($from);
        if ($from != $to) {
            $return = mb_convert_encoding($string, $to, $from);
        } else {
            $return = $string;
        }

        return $return;
    }

    /**
     * Checks whether a given string is valid in the specified encoding.
     *
     * ```
     * Encoding::Check('халлоу', Encoding::CP1251) returns false
     * Encoding::Check('халлоу', Encoding::UTF8) returns true
     * ```
     *
     * @param string $string The input string to be checked.
     * @param string $encoding The target encoding to validate against.
     *
     * @return bool True if the string is valid in the specified encoding, false otherwise.
     */
    public static function Check(string $string, string $encoding): bool
    {
        return mb_check_encoding($string, strtolower($encoding));
    }

    /**
     * Detects the encoding of a given string.
     *
     * ՝՝՝
     * Encoding::Detect('hello') returns 'utf8'
     * ՝՝՝
     *
     * @param string $string The input string to analyze.
     *
     * @return string The detected format or encoding.
     */
    public static function Detect(string $string): string
    {
        return strtolower(mb_detect_encoding($string, \mb_list_encodings(), false));
    }


}
