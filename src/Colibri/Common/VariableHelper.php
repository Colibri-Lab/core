<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

use Colibri\App;

/**
 * Helper class for work with variables
 */
class VariableHelper
{
    /**
     * Checks if a variable is empty.
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable is empty, false otherwise.
     */
    public static function IsEmpty(mixed $var): bool
    {
        if (is_object($var)) {
            return is_null($var);
        } elseif (is_array($var)) {
            return empty($var);
        }
        return $var === null || $var === "";
    }

    /**
     * Checks if the fields of an object are empty.
     *
     * @param mixed $object The object to check.
     * @return bool True if all fields are empty, false otherwise.
     */
    public static function IsObjectFieldsAreEmpty(mixed $object): bool
    {
        $isEmpty = true;
        if (!is_object($object) && !is_array($object)) {
            return self::IsEmpty($object);
        }

        foreach ((array) $object as $key => $value) {
            if (!self::IsObjectFieldsAreEmpty($value)) {
                $isEmpty = false;
                break;
            }
        }
        return $isEmpty;
    }

    /**
     * Checks if a variable is null.
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable is null, false otherwise.
     */
    public static function IsNull(mixed $var): bool
    {
        return is_null($var);
    }

    /**
     * Checks if a variable is an object.
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable is an object, false otherwise.
     */
    public static function IsObject(mixed $var): bool
    {
        return is_object($var);
    }

    /**
     * Checks if a variable is an array.
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable is an array, false otherwise.
     */
    public static function IsArray(mixed $var): bool
    {
        return is_array($var);
    }

    /**
     * Checks if a variable is a boolean (true or false).
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable is a boolean, false otherwise.
     */
    public static function IsBool(mixed $var): bool
    {
        return is_bool($var);
    }

    /**
     * Checks if a variable is a string.
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable is a string, false otherwise.
     */
    public static function IsString(mixed $var): bool
    {
        return is_string($var);
    }

    /**
     * Checks if a variable is numeric.
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable is numeric, false otherwise.
     */
    public static function IsNumeric(mixed $var): bool
    {
        return is_numeric($var);
    }

    /**
     * Checks if a variable represents a valid date.
     *
     * @param mixed $var The variable to check.
     * @return bool True if the variable represents a valid date, false otherwise.
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
     * Checks if a variable represents a valid time.
     *
     * @param mixed $value The variable to check.
     * @return bool True if the variable represents a valid time, false otherwise.
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
     * Changes the case of array values (keys remain unchanged).
     *
     * @param array $array The input array.
     * @param int $case The desired case (CASE_LOWER or CASE_UPPER, default is CASE_LOWER).
     * @return array|null The modified array with value case changed, or null if input is not an array.
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
     * Changes the case of array keys.
     *
     * @param array $array The input array.
     * @param int $case The desired case (CASE_LOWER or CASE_UPPER, default is CASE_LOWER).
     * @return array|null The modified array with keys in the specified case, or null if input is not an array.
     */
    public static function ChangeArrayKeyCase(array $array, int $case = CASE_LOWER): ?array
    {
        if (!is_array($array)) {
            return null;
        }

        $ret = [];
        foreach($array as $key => $value) {
            if($case === CASE_LOWER) {
                $ret[StringHelper::ToLower($key)] = $value;
            } elseif($case === CASE_UPPER) {
                $ret[StringHelper::ToUpper($key)] = $value;
            } else {
                $ret[$key] = $value;
            }
        }

        return array_change_key_case($ret, $case);
    }

    /**
     * Converts an object or an array to an associative array.
     *
     * @param object|array $object The object or array to convert.
     * @return array An associative array representation of the input.
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
     * Converts an array to an object.
     *
     * @param array $array The input array to be converted.
     *
     * @return mixed The resulting object.
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
     * Checks if an array is associative.
     *
     * An associative array is one where the keys are strings or non-sequential integers.
     * For example:
     *   - ['name' => 'John', 'age' => 30] is associative.
     *   - [0 => 'apple', 1 => 'banana'] is not associative.
     *
     * @param array $array The input array to check.
     *
     * @return bool Returns true if the array is associative, false otherwise.
     */
    public static function IsAssociativeArray(array|object $array): bool
    {
        if (is_object($array)) {
            return true;
        }

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
     * Converts a flat array to a tree structure.
     *
     * This function takes an input array and organizes it into a hierarchical tree.
     * Each element in the array represents a node, and the tree is formed based on
     * parent-child relationships defined by specific keys.
     *
     * @param array $array The flat input array to be transformed into a tree.
     * @param int $parent The default parent ID (usually 0 for the root).
     * @param string $parentName The key representing the parent ID in the array (default: 'parent').
     * @param string $childrenName The key to store child nodes (default: 'children').
     * @param string $keyName The key representing the unique identifier (default: 'id').
     *
     * @return array The resulting tree structure.
     */
    public static function ArrayToTree(
        array $array,
        int $parent = 0,
        string $parentName = 'parent',
        string $childrenName = 'children',
        string $keyName = 'id'
    ): array {
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

    /**
     * Converts a nested array or object to a flat associative array.
     *
     * This function recursively traverses the input array or object and flattens it into
     * a one-dimensional associative array. Each key in the resulting array represents a
     * path to the original nested value.
     *
     * @param array|object $var The input array or object to be flattened.
     * @param string $prefix An optional prefix to prepend to each flattened key.
     *
     * @return array The resulting flat associative array.
     */
    public static function ToPlane(array|object $var, string $prefix = ''): array
    {
        $ret = [];
        foreach($var as $key => $value) {
            $k = $prefix ? (is_string($key) ? '.' . $key : '[' . $key . ']') : $key;
            if((is_array($value) || is_object($value))) {
                $ret = array_merge($ret, self::ToPlane($value, $prefix . $k));
            } else {
                $ret[$prefix . $k] = $value;
            }
        }
        return $ret;
    }

    /**
     * Converts a nested array or object to a JSON-like structure with filters.
     *
     * This function recursively traverses the input array or object and constructs a
     * filtered representation suitable for JSON serialization. Filters can be applied
     * based on specific criteria.
     *
     * @param array|object $var The input array or object to be transformed.
     * @param string $prefix An optional prefix to prepend to each filter key.
     *
     * @return array The resulting JSON-like structure with filters.
     */
    public static function ToJsonFilters(array|object $var, string $prefix = ''): array
    {

        $ret = [];

        $var = self::ToPlane($var, $prefix);
        foreach($var as $key => $value) {
            if(StringHelper::EndsWith($key, ']')) {
                $res = preg_match_all('/\[(\d+)\]/', $key, $matches);
                if($res > 0) {
                    $lastMatch = end($matches[0]);
                    $lastMatchValue = end($matches[1]);
                    $key = substr($key, 0, strlen($key) - strlen($lastMatch));
                    if(!isset($ret[$key])) {
                        $ret[$key] = [];
                    }
                    $ret[$key][$lastMatchValue] = $value;
                }
            } else {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * Converts binary data to a hexadecimal string.
     *
     * This function takes a binary string as input and returns its hexadecimal representation.
     * Each byte in the input string corresponds to two hexadecimal characters in the output.
     *
     * @param string $data The binary data to be converted.
     *
     * @return string The hexadecimal representation of the input data.
     */
    public static function Bin2Hex(string $data): string
    {
        if (!is_string($data)) {
            return '';
        }
        return bin2hex($data);
    }

    /**
     * Converts a hexadecimal string to its binary representation.
     *
     * This function takes a hexadecimal string as input and returns its binary equivalent.
     * Each pair of hexadecimal characters corresponds to one byte in the output.
     *
     * @param string $data The hexadecimal string to be converted.
     *
     * @return string The binary representation of the input hexadecimal data.
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
     * Checks if a string represents serialized data.
     *
     * This function determines whether the input string is a valid serialized representation.
     * It checks if the string can be successfully unserialized using PHP's `unserialize` function.
     *
     * @param string $v The input string to check.
     *
     * @return bool Returns true if the string is serialized, false otherwise.
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
     * Serializes an object or value into a string representation.
     *
     * This function converts the given object or value into a storable string format.
     * Serialization is useful for saving data to files, databases, or transmitting it
     * across different systems.
     *
     * @param mixed $obj The object or value to be serialized.
     *
     * @return string The serialized representation of the input.
     */
    public static function Serialize(mixed $obj): string
    {
        return '0x' . VariableHelper::Bin2Hex(serialize($obj));
    }

    /**
     * Unserializes a string representation into a PHP value.
     *
     * This function takes a serialized string and converts it back into its original
     * PHP data structure. It reconstructs objects, arrays, and other complex types.
     *
     * @param string $string The serialized string to be unserialized.
     *
     * @return mixed The unserialized PHP value (bool, int, float, string, array, or object).
     *
     * @throws Throwable If unserialization fails due to invalid input or other errors.
     */
    public static function Unserialize(string $string): mixed
    {
        if (substr($string, 0, 2) == '0x') {
            $string = VariableHelper::Hex2Bin(substr($string, 2));
        }
        return @unserialize($string);
    }

    /**
     * Extends or merges two objects or arrays.
     *
     * This function combines the properties of two input objects or arrays into a single result.
     * By default, non-empty values from the second object/array overwrite corresponding values
     * in the first object/array. You can choose to merge recursively and handle empty values
     * differently using optional parameters.
     *
     * @param mixed $o1 The first object or array to be extended.
     * @param mixed $o2 The second object or array containing additional properties.
     * @param bool $recursive Whether to merge recursively (default: false).
     * @param bool $emptyAsUnset Whether to treat empty values as unset (default: false).
     * @param bool $removeEmptyValues Wheret to remove empty values from first array (default: false)
     *
     * @return mixed The extended or merged result.
     */
    public static function Extend(
        mixed $o1,
        mixed $o2,
        bool $recursive = false,
        bool $emptyAsUnset = false,
        bool $removeEmptyValues = false
    ): mixed {

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

        if($removeEmptyValues) {
            foreach ($o2 as $k => $v) {
                if($o2[$k] === null) {
                    unset($o1[$k]);
                }
            }
        }


        return $o1;
    }

    /**
     * Coalesces two values, returning the first non-null value.
     *
     * This function takes two input values and returns the first non-null value.
     * If both values are null, it returns the default value provided as the second argument.
     *
     * @param mixed $d The primary value to check.
     * @param mixed $def The default value to use if $d is null.
     *
     * @return mixed The first non-null value or the default value.
     */
    public static function Coalesce(mixed $d, mixed $def): mixed
    {
        if (is_null($d)) {
            return $def;
        }
        return $d;
    }

    /**
     * Converts an object or value to a formatted string representation.
     *
     * This function takes an input object or value and constructs a string representation
     * using specified separators and formatting options.
     *
     * @param mixed $object The object or value to be converted.
     * @param string $spl1 The primary separator (default: ' ').
     * @param string $spl2 The secondary separator (default: '=').
     * @param bool $quote Whether to enclose values in quotes (default: true).
     * @param string $keyPrefix An optional prefix for keys (default: '').
     *
     * @return string The formatted string representation.
     */
    public static function ToString(
        mixed $object,
        string $spl1 = ' ',
        string $spl2 = '=',
        bool $quote = true,
        string $keyPrefix = ''
    ): string {
        if(is_string($object)) {
            return $object;
        }

        if (
            !is_object($object) &&
            !is_array($object) ||
            !is_string($spl1) ||
            !is_string($spl2) ||
            !\is_bool(true) ||
            !is_string($keyPrefix)
        ) {
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
     * Converts a string containing PHP array data into an actual array.
     *
     * This function parses the input string and constructs an associative array based on
     * the PHP array syntax found in the string.
     *
     * @param string $string The input string containing PHP array data.
     *
     * @return array The resulting associative array.
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
     * Calculates the sum of values in an array.
     *
     * This function takes an input array and computes the sum of its numeric values.
     *
     * @param array $array The input array containing numeric values.
     *
     * @return float The total sum of the array values.
     */
    public static function Sum(array $array): float
    {
        if (!is_array($array) || empty($array)) {
            return 0;
        }
        return \array_sum($array);
    }

    /**
     * Checks if two values are similar.
     *
     * This function compares the actual value with the expected value and returns true
     * if they are considered similar. The definition of similarity depends on the context
     * and specific use case.
     *
     * @param mixed $actual The actual value to compare.
     * @param mixed $expected The expected value for comparison.
     *
     * @return bool Returns true if the values are similar, false otherwise.
     */
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

    /**
     * Converts a mixed value to an array.
     *
     * This function takes a mixed input (which can be an object, scalar, or other type)
     * and converts it into an array representation.
     *
     * @param mixed $object The input value to be converted.
     *
     * @return array The resulting array representation.
     */
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

    /**
     * Applies a closure to each element of an input value.
     *
     * This function takes an input value (which can be an object, array, or other type)
     * and applies the provided closure to each element. The result is an array or modified
     * value based on the closure's transformation.
     *
     * @param mixed $object The input value to be mapped.
     * @param \Closure|null $closure The closure to apply to each element (optional).
     *
     * @return mixed The result of applying the closure to each element.
     */
    public static function Map(mixed $object, ?\Closure $closure): mixed
    {
        if(!$closure) {
            return $object;
        }

        $newObject = [];
        foreach($object as $key => $value) {
            if(is_array($value) || is_object($value)) {
                [$key, $value] = $closure($key, self::Map($value, $closure));
            } else {
                [$key, $value] = $closure($key, $value);
            }
            $newObject[$key] = $value;
        }

        return $newObject;

    }

    /**
     * Filters the given object using the provided closure.
     *
     * This method applies a user-defined closure function to the provided object,
     * allowing for custom filtering or transformation of the object.
     *
     * @param mixed $object The object to be filtered. This can be any type of data.
     * @param \Closure|null $closure A closure function that defines the filtering logic. 
     *                               If null, no filtering is applied.
     * @return mixed The filtered object. The returned type depends on the closure's logic.
     */
    public static function Filter(mixed $object, ?\Closure $closure): mixed
    {
        if(!$closure) {
            return $object;
        }

        $newObject = [];
        foreach($object as $key => $value) {
            if($closure($key, $value)) {
                $newObject[$key] = $value;
            }
        }

        return $newObject;
    }

    /**
     * Converts a callable to its string representation.
     *
     * This function takes a callable (such as a closure or function) and returns its
     * string representation. The resulting string can be used to identify the callable.
     *
     * @param mixed $callable The callable to be converted.
     *
     * @return string The string representation of the callable.
     */
    public static function CallableToString(mixed $callable): string
    {
        $refl = new \ReflectionFunction($callable); // get reflection object
        $path = $refl->getFileName();  // absolute path of php file
        $begn = $refl->getStartLine(); // have to `-1` for array index
        $endn = $refl->getEndLine();
        $dlim = PHP_EOL;
        $list = explode($dlim, file_get_contents($path));         // lines of php-file source
        $list = array_slice($list, ($begn-1), ($endn-($begn-1))); // lines of closure definition
        $last = (count($list)-1); // last line number
        if(
            (substr_count($list[0], 'function') > 1) ||
            (substr_count($list[0], '{') > 1) ||
            (substr_count($list[$last], '}') > 1)
        ) {
            throw new \BadFunctionCallException(
                "Too complex context definition in: `$path`. Check lines: $begn & $endn."
            );
        }

        $list[0] = ('function'.explode('function', $list[0])[1]);
        $list[$last] = (explode('}', $list[$last])[0].'}');


        return implode($dlim, $list);
    }

    /**
     * Filters an array to keep only unique elements based on a specified property.
     *
     * This function takes an input array and removes duplicate elements based on the
     * value of a specific property (field). The resulting array contains only unique
     * elements with distinct property values.
     *
     * @param array $array The input array to filter.
     * @param string $field The name of the property to use for uniqueness comparison.
     *
     * @return array The filtered array with unique elements based on the specified property.
     */
    public static function UniqueByProperty(array $array, string $field): array
    {
        $keyMap = [];
        $newArray = [];
        foreach($array as $value) {
            if(isset($value[$field]) && !in_array($value[$field], $keyMap)) {
                $newArray[] = $value;
                $keyMap[] = $value[$field];
            }
        }
        return $newArray;
    }

    /**
     * Splits an array into parts of a specified length.
     *
     * This function divides the input array into smaller arrays (parts) with a maximum
     * length determined by the second argument. If the array length is not evenly divisible
     * by the specified part length, the last part may be shorter.
     *
     * @param array $array The input array to split.
     * @param int $partlength The desired length of each part.
     *
     * @return array An array of smaller arrays (parts).
     */
    public static function SplitArrayToParts($array, $partlength): array
    {
        $ret = [];
        while(!empty($array)) {
            $ret[] = array_splice($array, 0, $partlength);
        }
        return $ret;
    }

    /**
     * Converts a non-associative array into an associative array using specified keys and values.
     *
     * This function takes a non-associative input array and constructs an associative array
     * where each element is keyed by a specific field (property). Optionally, you can provide
     * a separate field for the associated values.
     *
     * @param array $array The input non-associative array.
     * @param mixed $fieldKey The field to use as keys for the associative array.
     * @param mixed|null $fieldValue The field to use as values (optional; defaults to null).
     *
     * @return array The resulting associative array.
     */
    public static function ConvertToAssotiative($array, $fieldKey, $fieldValue = null): array
    {
        $ret = [];
        foreach($array as $v) {
            $ret[$v[$fieldKey]] = $fieldValue ? $v[$fieldValue] : $v;
        }
        return $ret;
    }

    /**
     * Finds an object in array with key equals to value 
     * @param array $array
     * @param string $innerObjectKey
     * @param string $innerObjectValue
     * @return mixed 
     */
    public static function FindInArray(array $array, string $innerObjectKey, string $innerObjectvalue): mixed
    {
        foreach($array as $object) {
            $obj = (array)$object;
            if(isset($obj[$innerObjectKey]) && $obj[$innerObjectKey] == $innerObjectvalue) {
                return $object;
            }
        }        
        return null;
    }

}
