<?php

/**
 * Utils
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils
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
 * Класс обьект, все обьектноподобные классы и обьекты будут наследоваться от него
 */
class ExtendedObject implements ArrayAccess, IteratorAggregate, JsonSerializable, Countable
{
    use TEventDispatcher;

    /**
     * Содержит данные, которые получены при инициализации
     *
     * @var mixed
     */
    protected $_original;

    /**
     * Данные обьекта, свойства
     *
     * @var mixed
     */
    protected $_data;

    /**
     * Префикс свойств, для обеспечения правильной работы с хранилищами
     */
    protected ?string $_prefix = "";

    /**
     * Индикатор изменения обьекта
     */
    protected ?bool $_changed = false;

    protected ?bool $_changeKeyCase = true;

    /**
     * Схема валидации данных
     */
    public const JsonSchema = [
        'type' => 'object',
        'patternProperties' => [
            '.*' => [
                'type' => ['number','string','boolean','object','array','null']
            ]
        ]
    ];

    protected ValidationResult $_validationResult;

    /**
     * Конструктор
     *
     * @param mixed $data - инициализационные данные
     * @param string $prefix - префикс
     * @param boolean $changeKeyCase - изменить регистри ключей
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
     * Разрушает обьект
     *
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
     * Очищает все свойства
     *
     */
    public function Clear()
    {
        $this->_data = [];
    }

    /**
     * Установка данных в виде обьекта, полная замена
     *
     * @param mixed $data - данные
     */
    public function SetData($data)
    {
        $this->_data = $data;
        $this->_changed = true;
    }

    public function SetValidationSchema(object|array |string $schema)
    {
        $this->_changeKeyCase = false;
        self::$schema = $schema;
    }

    public function GetValidationData(): mixed
    {
        return $this->_data;
    }

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
            if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])[T|\s]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|60)(\.[0-9]+)?(Z|(\+|-)([01][0-9]|2[0-3]):([0-5][0-9]))?$/i', $value, $m)) {
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
                foreach($errors as $key => $value) {
                    $errorsString[] = $key . ': '  . $value;
                }
                $errorsString = implode("\n", $errorsString);
                $exception = new ValidationException($errorsString, 500, null, ['raw' => $validationError, 'formatted' => $errors]);
                $exception->Log(Logger::Debug);
                throw $exception;
                
            } else {
                return false;
            }
        }
        return true;

    }

    /**
     * Возвращает ассоциативный массив, содержащий все данные
     *
     * @param boolean $noPrefix - удалить префиксы из свойств
     */
    public function ToArray(bool $noPrefix = false): array
    {
        $data = array();
        foreach ($this->_data as $key => $value) {
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
            } elseif ((is_string($value) || is_object($value)) && method_exists($value, 'ToArray')) {
                $value = $value->ToArray($noPrefix);
            }
            $data[!$noPrefix ? $key : substr($key, strlen($this->_prefix))] = $value;
        }
        return $data;
    }

    /**
     * Возвращает данные, которые были переданы при инициализации
     *
     * @return \stdClass
     */
    public function Original(): object
    {
        return (object) $this->_original;
    }

    /**
     * Возвращает префикс полей обьекта
     *
     * @return string
     */
    public function Prefix(): string
    {
        return $this->_prefix;
    }

    /**
     * Изменен
     * @return bool 
     */
    public function IsChanged(): bool
    {
        return $this->_changed;
    }

    /**
     * Поле изменено
     * @return bool 
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
     * Возвращает текущие данные обьекта (без изменений)
     *
     * @param bool $type конвертировать ли данные
     * @return mixed
     */
    public function GetData($type = true)
    {
        return $type ? $this->_typeToData($this->_data) : $this->_data;
    }

    /**
     * Возвращает JSON строку данных обьекта
     *
     */
    public function ToJSON(): string
    {
        return json_encode($this->ToArray());
    }

    /**
     * Обновляет исходные данные
     * @return void 
     */
    public function UpdateOriginal()
    {
        $this->_original = $this->_data;
    }

    /**
     * Проверяет на наличие свойства в обьекте
     *
     * @param string $name название свойства
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     * Удаляет свойство из обьекта
     *
     * @param string $name название свойства
     */
    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    /**
     * Магическая функция
     *
     * @param string $property название свойства
     * @return mixed
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
     * Магическая функция
     *
     * @param string $property название свойства
     * @param mixed $value значение свойства
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
     * Обработчик по умолчанию события TypeExchange для замены типов
     *
     * @param string $mode - Режим 'get' или 'set'
     * @param string $property - название свойства
     * @param mixed $value значение свойства
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
     * Конвертирует данные для передачи через функцию GetData
     * @param mixed $data данные для конвертации
     * @return mixed конвертированные данные
     */
    protected function _typeToData(mixed $data)
    {
        return $data;
    }

    /**
     * Возвращает итератор
     * @return ExtendedObjectIterator 
     */
    public function getIterator()
    {
        return new ExtendedObjectIterator($this->GetData());
    }

    /**
     * Устанавливает значение по индексу
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        $this->$offset = $value;
    }

    /**
     * Проверяет есть ли данные по индексу
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        return isset($this->$offset);
    }

    /**
     * удаляет данные по индексу
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        unset($this->$offset);
    }

    /**
     * Возвращает значение по индексу
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!VariableHelper::IsString($offset)) {
            throw new InvalidArgumentException();
        }
        return $this->$offset;
    }

    public function jsonSerialize()
    {
        return $this->ToArray(true);
    }

    public function Count(): int
    {
        return count($this->_data);
    }

}
