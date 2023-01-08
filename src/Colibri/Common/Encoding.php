<?php

namespace Colibri\Common;


/**
 * @testFunction testEncoding
 */
class Encoding
{
    const UTF8 = "utf-8";
    const CP1251 = "windows-1251";
    const ISO_8859_1 = 'iso-8859-1';

    /**
     * Конвертирует кодировку строки
     * @param mixed $string строка, или обьект/массив в исходной кодировке
     * @param string $to результирующая кодировка
     * @param string $from исходная кодировка
     * @return mixed
     * @testFunction testEncodingConvert
     */
    public static function Convert(string|array |object $string, string $to, ?string $from = null): string|array |object
    {
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
     * Проверяет кодировку строки
     * @param string $string строка на проверку
     * @param string $encoding кодировка с которой нужно сравнить
     * @return bool
     * @testFunction testEncodingCheck
     */
    public static function Check(string $string, string $encoding): bool
    {
        return mb_check_encoding($string, strtolower($encoding));
    }

    /**
     * Возвращает кодировку строки
     * @param string $string строка
     * @return string
     * @testFunction testEncodingDetect
     */
    public static function Detect(string $string): string
    {
        return strtolower(mb_detect_encoding($string, \mb_list_encodings(), false));
    }


}