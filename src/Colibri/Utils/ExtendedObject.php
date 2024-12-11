<?php

/**
 * Utilities
 *
 * @package Colibri\Utils\Performance
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 * 
 */
namespace Colibri\Utils;

use ArrayAccess;
use Colibri\App;
use Colibri\AppException;
use Colibri\Collections\ArrayList;
use Colibri\Events\TEventDispatcher;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Logs\Logger;
use InvalidArgumentException;
use IteratorAggregate;
use Colibri\Exceptions\ValidationException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Traversable;
use JsonSerializable;
use Countable;
use Colibri\Common\StringHelper;
use Opis\JsonSchema\Validator;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Errors\ValidationError;

/**
 * Base class for objects. All object-like classes and instances will inherit from this class.
 */
class ExtendedObject implements ArrayAccess, IteratorAggregate, JsonSerializable, Countable
{
    use TEventDispatcher;

    /**
     * Contains data received during initialization.
     *
     * @var mixed
     */
    protected $_original;

    /**
     * Object data, properties.
     *
     * @var mixed
     */
    protected $_data;

    /**
     * Prefix for properties, to ensure proper operation with storages.
     */
    protected ?string $_prefix = "";

    /**
     * Object change indicator.
     */
    protected ?bool $_changed = false;

    /**
     * Change case on object keys
     */
    protected ?bool $_changeKeyCase = true;

    /**
     * Data validation schema.
     */
    public const JsonSchema = [
        'type' => 'object',
        'patternProperties' => [
            '.*' => [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null']
            ]
        ]
    ];

    /**
     * Validation results
     */
    protected ValidationResult $_validationResult;

    /**
     * Constructor
     *
     * @param mixed $data - initialization data
     * @param string $prefix - prefix
     * @param boolean $changeKeyCase - change key case
     */
    public function __construct($data = null, $prefix = "", $changeKeyCase = true)
    {
        if (is_null($data)) {
            $this->_data = [];
        } else {
            if ($data instanceof ExtendedObject) {
                $this->_data = $data->GetData(false);
                $this->_prefix = $data->prefix;
            } elseif (is_array($data)) {
                $this->_data = $data;
            } elseif (!$data) {
                $this->_data = [];
            } else {
                $this->_data = get_object_vars($data);
            }
        }

        if (!empty($prefix) && substr($prefix, strlen($prefix) - 1, 1) != "_") {
            $prefix = $prefix . "_";
        }

        $this->_changeKeyCase = $changeKeyCase;
        if ($this->_changeKeyCase) {
            $this->_data = array_change_key_case($this->_data, CASE_LOWER);
        }

        $this->_prefix = $prefix;
        $this->_original = $this->_data;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        unset($this->_data);
    }

    public static function Schema(bool $exportAsString = false, $addElements = []): object|string
    {

        $schema = VariableHelper::Extend([], static::JsonSchema);
        if (!empty($addElements)) {
            $schema = array_merge($schema, $addElements);
        }

        if ($exportAsString) {
            return json_encode($schema);
        }

        return (object) $schema;
    }

    /**
     * Cleans object
     */
    public function Clear()
    {
        $this->_data = [];
    }

    /**
     * Sets data for the object.
     *
     * This method allows setting data for the object. It can accept various types of data,
     * such as arrays, objects, or ExtendedObject instances.
     *
     * @param mixed $data The data to set for the object.
     * 
     * @return void
     */
    public function SetData($data)
    {
        $this->_data = $data;
        $this->_changed = true;
    }

    /**
     * Retrieves the validation data used for schema validation.
     *
     * This method returns the data that is used for schema validation. It can be useful
     * for debugging or accessing the data before validation.
     *
     * @return mixed The validation data used for schema validation.
     */
    public function GetValidationData(): mixed
    {
        return $this->_data;
    }

    /**
     * Validates the object's data against a JSON schema.
     *
     * This method validates the object's data against a predefined JSON schema.
     * If the validation fails, it returns false. Optionally, you can set
     * $throwExceptions to true to throw a ValidationException upon failure.
     *
     * @param bool $throwExceptions Whether to throw a ValidationException upon failure.
     * @return bool True if the object's data is valid according to the schema, false otherwise.
     * @throws ValidationException If $throwExceptions is true and the validation fails.
     */
    public function Validate(bool $throwExceptions = false): bool
    {
        if (empty(static::JsonSchema)) {
            if ($throwExceptions) {
                throw new AppException('Schema is empty, can not validate');
            } else {
                return false;
            }
        }

        $schemaData = static::JsonSchema;
        $schemaData = json_decode(json_encode($schemaData));
        $data = $this->GetValidationData();
        $data = VariableHelper::ArrayToObject($data);

        $validator = new Validator();

        $formats = $validator->parser()->getFilterResolver();
        $isDbDateTime = function (string $value): bool {
            if (is_numeric($value)) {
                $d = new \DateTime();
                $d->setTimestamp((int)$value);
                $value = $d->format(\DateTime::W3C);
            }
            if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])[T|\s]([01][0-9]|2[0-3]):'.
                '([0-5][0-9]):([0-5][0-9]|60)(\.[0-9]+)?(Z|(\+|-)([01][0-9]|2[0-3]):([0-5][0-9]))?$/i', $value, $m)) {
                return checkdate($m[2], $m[3], $m[1]);
            }
            return false;
        };
        $formats->registerCallable("string", "db-date-time", $isDbDateTime);

        $validator->setMaxErrors(100);
        /** @var ValidationResult $result */
        $this->_validationResult = $validator->validate($data, $schemaData);
        if ($this->_validationResult->hasError()) {
            if ($throwExceptions) {
                /** @var ValidationError */
                $validationError = $this->_validationResult->error();
                $formatter = new ErrorFormatter();
                $errors = $formatter->format($validationError, false);
                $errorsString = [];
                foreach ($errors as $key => $value) {
                    $errorsString[] = $key . ': ' . $value;
                }
                $errorsString = implode("\n", $errorsString);
                $exception = new ValidationException(
                    $errorsString,
                    500,
                    null,
                    ['formatted' => $errors, 'data' => $data, 'schema' => static::JsonSchema]
                ); // 'raw' => $validationError,
                $exception->Log(Logger::Debug);
                throw $exception;

            } else {
                return false;
            }
        }
        return true;

    }

    /**
     * Converts the object's data to an associative array.
     *
     * This method converts the object's data to an associative array.
     * Optionally, you can specify whether to exclude the prefix from property names
     * and provide a callback function to filter the properties included in the array.
     *
     * @param bool $noPrefix Whether to exclude the prefix from property names.
     * @param \Closure|null $callback A callback function to filter properties included in the array.
     * @return array An associative array representing the object's data.
     */
    public function ToArray(bool $noPrefix = false, ?\Closure $callback = null): array
    {
        $data = array();
        foreach ($this->_data as $key => $value) {
            if($callback && !$callback($key, $value)) {
                continue;
            }
            $value = $this->_typeExchange('get', $key);
            if (is_array($value) || $value instanceof ArrayList) {
                $ret = [];
                foreach ($value as $index => $v) {
                    if ((is_string($v) || is_object($v)) && method_exists($v, 'ToArray')) {
                        $ret[$index] = $v->ToArray($noPrefix);
                    } else {
                        $ret[$index] = $v;
                    }
                }
                $value = $ret;
            } elseif (is_object($value) && method_exists($value, 'ToArray')) {
                $value = $value->ToArray($noPrefix);
            }
            $data[!$noPrefix ? $key : substr($key, strlen($this->_prefix))] = $value;
        }
        return $data;
    }

    /**
     * Returns the original data provided during object initialization.
     *
     * This method returns the original data provided during object initialization
     * as an object. The original data represents the state of the object before any changes.
     *
     * @return object An object containing the original data provided during initialization.
     */
    public function Original(): object
    {
        return (object) $this->_original;
    }

    /**
     * Resets the original data to empty object
     * @property string $property
     * @return void
     */
    public function ResetOriginal(?string $property = null): void
    {
        if($property === null) {
            $this->_original = (object)[];
        } else {
            $original = (array) $this->_original;
            unset($original[$this->_prefix . $property]);
            $this->_original = (object) $original;
        }
    }

    /**
     * Retrieves the prefix used for object properties.
     *
     * This method returns the prefix used for object properties. The prefix is typically
     * set during object initialization and is useful for distinguishing properties
     * when working with storage systems.
     *
     * @return string The prefix used for object properties.
     */
    public function Prefix(): string
    {
        return $this->_prefix;
    }

    /**
     * Checks if the object has been changed.
     *
     * This method determines whether any changes have been made to the object's data
     * since its initialization or the last update. It returns true if changes have
     * been made, indicating that the object state is different from its original state;
     * otherwise, it returns false.
     *
     * @return bool True if the object has been changed; otherwise, false.
     */
    public function IsChanged(): bool
    {
        return $this->_changed;
    }

    /**
     * Checks if a specific property of the object has been changed.
     *
     * This method determines whether a particular property of the object has been
     * modified since its initialization or the last update. It returns true if the
     * specified property has been changed; otherwise, it returns false.
     *
     * @param string $property The name of the property to check for changes.
     * @param bool $dummy Optional. A dummy parameter to maintain method signature consistency.
     * @return bool True if the specified property has been changed; otherwise, false.
     */
    public function IsPropertyChanged(string $property, bool $dummy = false): bool
    {

        if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
        }
        if (!isset($this->Original()->$property) && isset($this->_data[$property])) {
            return true;
        } elseif (!isset($this->_data[$property]) && !isset($this->Original()->$property)) {
            return false;
        } else {
            return $this->Original()->$property != $this->_data[$property];
        }
    }

    /**
     * Retrieves the current data of the object.
     *
     * This method returns the current data of the object. If the $type parameter is set to true,
     * the data is converted according to the object's type conversion rules; otherwise, the raw data
     * is returned without conversion.
     *
     * @param bool $type Optional. Determines whether to convert the data according to type conversion rules.
     *                   Defaults to true.
     * @return mixed The current data of the object.
     */
    public function GetData($type = true)
    {
        return $type ? $this->_typeToData($this->_data) : $this->_data;
    }

    /**
     * Converts the object's data to a JSON string.
     *
     * This method serializes the object's data into a JSON string representation.
     *
     * @return string The JSON string representing the object's data.
     */
    public function ToJSON(): string
    {
        return json_encode($this->ToArray());
    }

    /**
     * Updates the original data with the current data of the object.
     *
     * This method updates the original data of the object with its current data.
     * It is typically used after modifying the object's data to synchronize the original and current states.
     *
     * @return void
     */
    public function UpdateOriginal()
    {
        $this->_original = $this->_data;
    }

    /**
     * Checks whether a property is set in the object.
     *
     * This magic method is triggered when attempting to check whether a property is set in the object using isset().
     *
     * @param string $property The name of the property being checked.
     * @return bool Returns true if the property is set, false otherwise.
     */
    public function __isset($property)
    {
        if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
        }

        return isset($this->_data[$property]);
    }

    /**
     * Unsets a property in the object.
     *
     * This magic method is triggered when attempting to unset a property in the object using unset().
     *
     * @param string $property The name of the property to unset.
     * @return void
     */
    public function __unset($property)
    {
        if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
        }
        unset($this->_data[$property]);
    }

    /**
     * Retrieves the value of a dynamic or inaccessible property from the object.
     *
     * This magic method is invoked when attempting to access a property that is not accessible or does not exist
     * within the object's scope using the arrow operator (->).
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed The value of the specified property.
     */
    public function __get(string $property): mixed
    {

        $propertyCamelCased = StringHelper::ToCamelCaseVar($property, true);
        if (method_exists($this, 'getProperty' . $propertyCamelCased)) {
            $value = $this->{'getProperty' . $propertyCamelCased}();
        } else {

            $property = $this->_changeKeyCase ? strtolower($property) : $property;
            if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
                $property = $this->_prefix . $property;
            }

            $value = $this->_typeExchange('get', $property);

        }

        return $value;
    }

    /**
     * Sets the value of a dynamic or inaccessible property within the object.
     *
     * This magic method is invoked when attempting to assign a value to a property that is not accessible or does not exist
     * within the object's scope using the arrow operator (->).
     *
     * @param string $property The name of the property to set.
     * @param mixed $value The value to assign to the property.
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        $propertyCamelCased = StringHelper::ToCamelCaseVar($property, true);
        if (method_exists($this, 'setProperty' . $propertyCamelCased)) {
            $this->{'setProperty' . $propertyCamelCased}($value);
        } else {
            $property = $this->_changeKeyCase ? strtolower($property) : $property;
            if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
                $property = $this->_prefix . $property;
            }
            $this->_typeExchange('set', $property, $value);
            $this->_changed = true;
        }
    }

    /**
     * Handles the exchange of property values based on the specified mode.
     *
     * This method is responsible for managing the exchange of property values based on the specified mode ('get' or 'set').
     * It is used internally to retrieve or assign values to object properties dynamically.
     *
     * @param string $mode The mode of operation ('get' or 'set').
     * @param string $property The name of the property to interact with.
     * @param mixed $value The value to assign to the property (only applicable in 'set' mode).
     * @return mixed The value of the property (in 'get' mode) or null (in 'set' mode).
     */
    protected function _typeExchange(string $mode, string $property, mixed $value = null): mixed
    {
        if ($mode == 'get') {
            return isset($this->_data[$property]) ? $this->_data[$property] : null;
        } else {
            $this->_data[$property] = $value;
            return null;
        }
    }

    /**
     * Converts data for retrieval via the GetData method.
     *
     * This method is responsible for converting data to be retrieved via the GetData method.
     * It is intended to be overridden in subclasses to customize the conversion behavior if needed.
     *
     * @param mixed $data The data to be converted.
     * @return mixed The converted data.
     */
    protected function _typeToData(mixed $data)
    {
        return $data;
    }

    /**
     * Returns an iterator for iterating over the data of the ExtendedObject.
     *
     * This method returns an iterator that allows iterating over the data of the ExtendedObject.
     * It enables the use of foreach loops and other iterable operations on ExtendedObject instances.
     *
     * @return ExtendedObjectIterator An iterator for the data of the ExtendedObject.
     */
    public function getIterator()
    {
        return new ExtendedObjectIterator($this->GetData());
    }

    /**
     * Sets the value at the specified offset.
     *
     * This method sets the value at the specified offset in the ExtendedObject. It allows
     * setting values using array access syntax, e.g., $object['key'] = $value.
     *
     * @param string|int $offset The offset at which to set the value.
     * @param mixed $value The value to set at the specified offset.
     * @return void
     * @throws InvalidArgumentException If the offset is not a string or an integer.
     */
    public function offsetSet($offset, $value)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        $this->$offset = $value;
    }

    /**
     * Checks if a value exists at the specified offset.
     *
     * This method checks if a value exists at the specified offset in the ExtendedObject. It allows
     * checking the existence of values using array access syntax, e.g., isset($object['key']).
     *
     * @param string|int $offset The offset to check.
     * @return bool True if a value exists at the specified offset, false otherwise.
     * @throws InvalidArgumentException If the offset is not a string or an integer.
     */
    public function offsetExists($offset)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        return isset($this->$offset);
    }

    /**
     * Unsets the value at the specified offset.
     *
     * This method unsets the value at the specified offset in the ExtendedObject. It allows
     * unsetting values using array access syntax, e.g., unset($object['key']).
     *
     * @param string|int $offset The offset of the value to unset.
     * @return void
     * @throws InvalidArgumentException If the offset is not a string or an integer.
     */
    public function offsetUnset($offset)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        unset($this->$offset);
    }

    /**
     * Retrieves the value at the specified offset.
     *
     * This method retrieves the value at the specified offset in the ExtendedObject. It allows
     * accessing values using array access syntax, e.g., $value = $object['key'].
     *
     * @param string|int $offset The offset of the value to retrieve.
     * @return mixed|null The value at the specified offset, or null if the offset does not exist.
     * @throws InvalidArgumentException If the offset is not a string or an integer.
     */
    public function offsetGet($offset)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        return $this->$offset;
    }

    /**
     * Serializes the object to a value that can be serialized by json_encode.
     *
     * This method is called during JSON serialization of the object. It allows the object
     * to be serialized to a JSON-serializable representation using the json_encode function.
     *
     * @return array The serializable representation of the object.
     */
    public function jsonSerialize()
    {
        return $this->ToArray(true);
    }

    /**
     * Constructs a new object instance from a JSON-encoded string.
     *
     * This method creates a new instance of the class from a JSON-encoded string. It accepts
     * a JSON string representing the object and returns a new instance of the class.
     *
     * @param string $json The JSON-encoded string representing the object.
     * @return static The new instance of the class.
     */
    public static function JsonUnserialize(string $json): static
    {
        return new static(json_decode($json));
    }

    /**
     * Counts the number of elements in the object.
     *
     * This method returns the number of elements in the object.
     *
     * @return int The number of elements in the object.
     */
    public function Count(): int
    {
        return count($this->_data);
    }

}
