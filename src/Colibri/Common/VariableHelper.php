<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Helpers
 */

namespace Colibri\Common;
use Colibri\App;

/**
 * Обертки на всякие разные функции PHP
 */
class VariableHelper
{

    /**
     * Проверить пустое ли значение в переменной
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsEmpty
     */
    public static function IsEmpty($var)
    {
        if (is_object($var)) {
            return is_null($var);
        } else if (is_array($var)) {
            return empty($var);
        }
        return ($var === null || $var === "");
    }

    /**
     * Проверка на NULL
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsNull
     */
    public static function IsNull($var)
    {
        return is_null($var);
    }

    /**
     * Проверить обьект ли в переменной
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsObject
     */
    public static function IsObject($var)
    {
        return is_object($var);
    }

    /**
     * Проверить массив ли в переменной
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsArray
     */
    public static function IsArray($var)
    {
        return is_array($var);
    }

    /**
     * Проверка на true/false
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsBool
     */
    public static function IsBool($var)
    {
        return is_bool($var);
    }

    /**
     * Проверить не строка ли в переменной
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsString
     */
    public static function IsString($var)
    {
        return is_string($var);
    }

    /**
     * Проверить не число ли в переменной
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsNumeric
     */
    public static function IsNumeric($var)
    {
        return is_numeric($var);
    }

    /**
     * Проверить не дата ли в переменной
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsDate
     */
    public static function IsDate($var)
    {
        if (!$var || is_null($var)) {
            return false;
        }

        if (is_string($var)) {
            return strtotime($var) !== false;
        }

        return true;
    }

    /**
     * Проверить не время ли в переменной
     *
     * @param mixed $value
     * @return boolean
     * @testFunction testVariableHelperIsTime
     */
    public static function IsTime($value)
    {
        if (preg_match('/(\d{2}):(\d{2})/', $value, $matches) > 0) {
            if (is_numeric($matches[1]) && is_numeric($matches[2])) {
                return $matches[1] < 24 && $matches[2] < 60;
            }

            return false;
        }
        return false;
    }

    /**
     * Изменить регистр значений
     *
     * @param array $array
     * @param int $case
     * @return array|null
     * @testFunction testVariableHelperChangeArrayValueCase
     */
    public static function ChangeArrayValueCase($array, $case = CASE_LOWER)
    {
        if (!is_array($array)) {
            return null;
        }
        foreach ($array as $i => $value) {
            $array[$i] = $case == CASE_LOWER ? StringHelper::ToLower($value) : StringHelper::ToUpper($value);
        }
        return $array;
    }

    /**
     * Изменить регистр ключей
     *
     * @param array $array
     * @param int $case
     * @return array|null
     * @testFunction testVariableHelperChangeArrayKeyCase
     */
    public static function ChangeArrayKeyCase($array, $case = CASE_LOWER)
    {
        if (!is_array($array)) {
            return null;
        }
        return array_change_key_case($array, $case);
    }

    /**
     * Превратить обьект в массив рекурсивно
     *
     * @param object|array $object
     * @return array
     * @testFunction testVariableHelperObjectToArray
     */
    public static function ObjectToArray($object)
    {
        if(!self::IsObject($object) && !self::IsArray($object)) {
            return (array) $object;
        }

        $object = (array)$object;
        foreach ($object as $k => $v) {
            $object[$k] = self::ObjectToArray($v);
        }

        return (array) $object;


    }

    /**
     * Превратить массив в обьект рекурсивно
     *
     * @param array|object $array
     * @return object|null
     * @testFunction testVariableHelperArrayToObject
     */
    public static function ArrayToObject($array)
    {
        if(!self::IsObject($array) && !self::IsArray($array)) {
            return $array;
        }

        $array = (array)$array;
        foreach ($array as $k => $v) {
            $array[$k] = self::ArrayToObject($v);
        }
        return (object) $array;

    }

    /**
     * Проверяет ассоциативный ли массив
     *
     * @param array $array
     * @return boolean
     * @testFunction testVariableHelperIsAssociativeArray
     */
    public static function IsAssociativeArray($array)
    {
        if (!is_array($array)) {
            return false;
        }

        $keys = array_keys($array);
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Собирает массив в дерево
     * @param array $array 
     * @param int $parent 
     * @param string $parentName 
     * @param string $childrenName 
     * @return array 
     */
    public static function ArrayToTree($array, $parent = 0, $parentName = 'parent', $childrenName = 'children', $keyName = 'id')
    {
        $array = array_combine(array_column($array, $keyName), array_values($array));
    
        foreach ($array as $k => &$v) {
            if (isset($array[(int)$v[$parentName]])) {
                $array[(int)$v[$parentName]][$childrenName][(int)$k] = &$v;
            }
            unset($v);
        }
    
        return array_filter($array, function($v) use ($parent, $parentName) {
            return $v[$parentName] == $parent;
        });
    }

    /**
     * Превратить текст в 16-ричное представление
     *
     * @param string $data
     * @return string
     * @testFunction testVariableHelperBin2Hex
     */
    public static function Bin2Hex($data)
    {
        if (!is_string($data)) {
            return '';
        }
        return bin2hex($data);
    }

    /**
     * Из 16-ричного в обычный текст
     *
     * @param string $data
     * @return string
     * @testFunction testVariableHelperHex2Bin
     */
    public static function Hex2Bin($data)
    {
        if (!is_string($data)) {
            return '';
        }

        $len = strlen($data);
        try {
            return pack("H" . $len, $data);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Проверить сериализованный ли это обьект
     *
     * @param string $v
     * @return boolean
     * @testFunction testVariableHelperIsSerialized
     */
    public static function isSerialized($v)
    {
        if (!is_string($v)) {
            return false;
        }

        if (substr($v, 0, 2) == '0x') {
            $v = VariableHelper::Hex2Bin(substr($v, 2));
        }

        if ($v === serialize(false)) {
            return true;
        }

        $vv = @unserialize($v);
        if ($vv === true || is_array($vv) || is_object($vv) || is_numeric($vv) || is_string($vv)) {
            return true;
        }
        return false;
    }

    /**
     * Сериализовать в строку
     *
     * @param mixed $obj
     * @return string
     * @testFunction testVariableHelperSerialize
     */
    public static function Serialize($obj)
    {
        return '0x' . VariableHelper::Bin2Hex(serialize($obj));
    }

    /**
     * Десериализовать из строки
     *
     * @param string $string
     * @return mixed
     * @testFunction testVariableHelperUnserialize
     */
    public static function Unserialize($string)
    {
        if (substr($string, 0, 2) == '0x') {
            $string = VariableHelper::Hex2Bin(substr($string, 2));
        }
        return @unserialize($string);
    }

    /**
     * Копирует 2 обьекта/массива в один, с заменой существующий значений
     * Аналог jQuery.extend
     *
     * @param mixed $o1
     * @param mixed $o2
     * @return mixed
     * @testFunction testVariableHelperExtend
     */
    public static function Extend($o1, $o2, $recursive = false)
    {

        if($recursive && !is_array($o2) && !is_object($o2)) {
            return $o2;
        }

        $o1 = (array)$o1;
        $o2 = (array)$o2;

        foreach ($o1 as $k => $v) {
            if (isset($o2[$k])) {
                $o1[$k] = $recursive ? VariableHelper::Extend($o1[$k], $o2[$k], $recursive) : $o2[$k];
            }
        }

        foreach ($o2 as $k => $v) {
            if (!isset($o1[$k])) {
                $o1[$k] = $v;
            }
        }

        return $o1;
    }

    /**
     * Проверяет если d=null то возвращает def
     *
     * @param mixed $d
     * @param mixed $def
     * @return mixed
     * @testFunction testVariableHelperCoalesce
     */
    public static function Coalesce($d, $def)
    {
        if (is_null($d)) {
            return $def;
        }
        return $d;
    }

    /**
     * Собирает массив/обьект в строку
     *
     * @param mixed $object
     * @param string $spl1
     * @param string $spl2
     * @param boolean $quote
     * @param string $keyPrefix
     * @return string
     * @testFunction testVariableHelperToString
     */
    public static function ToString($object, $spl1 = ' ', $spl2 = '=', $quote = true, $keyPrefix = '')
    {

        if (!is_object($object) && !is_array($object) || !is_string($spl1) || !is_string($spl2) || !\is_bool(true) || !is_string($keyPrefix)) {
            return false;
        }

        $ret = array();
        $object = (array)$object;
        foreach ($object as $k => $v) {
            $ret[] = $keyPrefix . $k . $spl2 . ($quote ? '"' : '') . StringHelper::PrepareAttribute($v) . ($quote ? '"' : '');
        }
        return implode($spl1, $ret);
    }

    /**
     * Возвращает обьект из вывода var_dump
     * @param string $string
     * @testFunction testVariableHelperFromPhpArrayOutput
     */
    public static function FromPhpArrayOutput($string)
    {
        $ret = array();
        $lines = explode("\n", $string);
        foreach ($lines as $line) {
            if (trim($line, "\r\t\n ") === '') {
                continue;
            }

            $parts = explode("=>", trim($line, "\r\t\n "));

            $value = end($parts);
            $key = reset($parts);
            $key = trim($key, "[] ");
            $ret[$key] = $value;
        }

        return $ret;
    }

    /**
     * Выполняет сумирование всех элементов массива
     * @param array $array массив чисел
     * @return float результат
     * @testFunction testVariableHelperSum
     */
    public static function Sum($array)
    {
        if (!is_array($array) || count($array) == 0) {
            return 0;
        }
        return \array_sum($array);
    }

    public static function IsSimilar($actual, $expected) {
        try {
            if((!is_array($actual) && !is_object($actual)) || (!is_array($expected) && !is_object($expected))) {
                return $actual == $expected;
            }
            
            $actual = (array)$actual;
            $expected = (array)$expected;
            foreach ($expected as $key => $value) {
                if (!self::IsSimilar($actual[$key], $expected[$key])) {
                    return false;
                }
            }
            foreach ($actual as $key => $value) {
                if (!self::IsSimilar($actual[$key], $expected[$key])) {
                    return false;
                }
            }
            return true;
        }
        catch(\Throwable $e) {
            App::$log->debug($e->getMessage());
            return false;
        }
    }
    
}
