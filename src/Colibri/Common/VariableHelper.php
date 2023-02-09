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
    public static function IsEmpty(mixed $var): bool
    {
        if (is_object($var)) {
            return is_null($var);
        } elseif (is_array($var)) {
            return empty($var);
        }
        return ($var === null || $var === "");
    }

    public static function IsObjectFieldsIsEmpty(mixed $object): bool
    {
        $isEmpty = true;
        if (!is_object($object) && !is_array($object)) {
            return self::IsEmpty($object);
        }

        foreach ((array) $object as $key => $value) {
            if (!self::IsObjectFieldsIsEmpty($value)) {
                $isEmpty = false;
                break;
            }
        }
        return $isEmpty;
    }

    /**
     * Проверка на NULL
     *
     * @param mixed $var
     * @return boolean
     * @testFunction testVariableHelperIsNull
     */
    public static function IsNull(mixed $var): bool
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
    public static function IsObject(mixed $var): bool
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
    public static function IsArray(mixed $var): bool
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
    public static function IsBool(mixed $var): bool
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
    public static function IsString(mixed $var): bool
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
    public static function IsNumeric(mixed $var): bool
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
    public static function IsDate(mixed $var): bool
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
    public static function IsTime(mixed $value): bool
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
    public static function ChangeArrayValueCase(array $array, int $case = CASE_LOWER): ?array
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
    public static function ChangeArrayKeyCase(array $array, int $case = CASE_LOWER): ?array
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
    public static function ObjectToArray(object|array $object): array
    {
        if (!self::IsObject($object) && !self::IsArray($object)) {
            return (array) $object;
        }

        $object = (array) $object;
        foreach ($object as $k => $v) {
            $object[$k] = self::ObjectToArray($v);
        }

        return (array) $object;


    }

    /**
     * Превратить массив в обьект рекурсивно
     *
     * @param array|object|string $array
     * @return object|array|string
     * @testFunction testVariableHelperArrayToObject
     */
    public static function ArrayToObject(mixed $array): mixed
    {
        if (is_null($array)) {
            return null;
        }

        if (!self::IsObject($array) && !self::IsArray($array)) {
            return $array;
        }

        if (self::IsArray($array) && !self::IsAssociativeArray($array)) {
            foreach ($array as $index => $v) {
                $array[$index] = self::ArrayToObject($v);
            }
            return $array;
        }

        $array = (array) $array;
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
    public static function IsAssociativeArray(array $array): bool
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
    public static function ArrayToTree(array $array, int $parent = 0, string $parentName = 'parent', string $childrenName = 'children', string $keyName = 'id'): array
    {
        $array = array_combine(array_column($array, $keyName), array_values($array));

        foreach ($array as $k => &$v) {
            if (isset($array[(int) $v[$parentName]])) {
                $array[(int) $v[$parentName]][$childrenName][(int) $k] = & $v;
            }
            unset($v);
        }

        return array_filter($array, function ($v) use ($parent, $parentName) {
            return $v[$parentName] == $parent;
        });
    }

    public static function ToPlane(array|object $var, string $prefix = ''): array
    {
        $ret = [];
        foreach($var as $key => $value) {
            $k = $prefix ? (is_string($key) ? '.' . $key : '[' . $key . ']') : $key;
            if(is_array($value) || is_object($value)) {
                $ret = array_merge($ret, self::ToPlane($value, $prefix . $k));
            } else {
                $ret[$prefix . $k] = $value;                    
            } 
        }
        return $ret;
    }

    /**
     * Превратить текст в 16-ричное представление
     *
     * @param string $data
     * @return string
     * @testFunction testVariableHelperBin2Hex
     */
    public static function Bin2Hex(string $data): string
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
    public static function Hex2Bin(string $data): string
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
    public static function isSerialized(string $v): bool
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
    public static function Serialize(mixed $obj): string
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
    public static function Unserialize(string $string): mixed
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
    public static function Extend(mixed $o1, mixed $o2, bool $recursive = false, bool $emptyAsUnset = false): mixed
    {

        if ($recursive && !is_array($o2) && !is_object($o2)) {
            return $o2;
        }

        $o1 = (array) $o1;
        $o2 = (array) $o2;

        foreach ($o1 as $k => $v) {
            if (isset($o2[$k]) && (!$emptyAsUnset || ($emptyAsUnset && $o2[$k]))) {
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
    public static function Coalesce(mixed $d, mixed $def): mixed
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
    public static function ToString(mixed $object, string $spl1 = ' ', string $spl2 = '=', bool $quote = true, string $keyPrefix = ''): string
    {

        if (!is_object($object) && !is_array($object) || !is_string($spl1) || !is_string($spl2) || !\is_bool(true) || !is_string($keyPrefix)) {
            return false;
        }

        $ret = array();
        $object = (array) $object;
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
    public static function FromPhpArrayOutput(string $string): array
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
    public static function Sum(array $array): float
    {
        if (!is_array($array) || count($array) == 0) {
            return 0;
        }
        return \array_sum($array);
    }

    public static function IsSimilar(mixed $actual, mixed $expected): bool
    {
        try {
            if ((!is_array($actual) && !is_object($actual)) || (!is_array($expected) && !is_object($expected))) {
                return $actual == $expected;
            }

            $actual = (array) $actual;
            $expected = (array) $expected;
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
        } catch (\Throwable $e) {
            App::$log->debug($e->getMessage());
            return false;
        }
    }

    public static function MixedToArray(mixed $object): mixed
    {
        $typeName = gettype($object);
        if ($typeName === 'object') {
            $className = get_class($object);
            if ($className === 'stdClass') {
                $array = [
                    '__class' => $className
                ];
                foreach ($object as $property => $value) {
                    $array[$property] = self::MixedToArray($value);
                }
                return $array;
            } else {
                $reflectionClass = new \ReflectionClass($className);
                $array = [
                    '__class' => $className
                ];
                foreach ($reflectionClass->getProperties() as $property) {
                    $property->setAccessible(true);
                    $array[$property->getName()] = self::MixedToArray($property->getValue($object));
                    $property->setAccessible(false);
                }

                return $array;
            }

        } elseif ($typeName === 'array') {
            $array = [];
            foreach ($object as $property => $value) {
                $array[$property] = self::MixedToArray($value);
            }
            return $array;
        } else {
            return $object;
        }

    }


}