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
use Colibri\Events\TEventDispatcher;
use Colibri\Common\VariableHelper;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use JsonSerializable;
use Countable;
use Colibri\Common\StringHelper;

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
     *
     * @var string
     */
    protected $_prefix = "";

    /**
     * Индикатор изменения обьекта
     *
     * @var boolean
     */
    protected $_changed = false;

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
            } else if(!$data) {
                $this->_data = [];
            }
            else {
                $this->_data = get_object_vars($data);
            }
        }

        if (!empty($prefix) && substr($prefix, strlen($prefix) - 1, 1) != "_") {
            $prefix = $prefix . "_";
        }

        if($changeKeyCase) {
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

    /**
     * Возвращает ассоциативный массив, содержащий все данные
     *
     * @param boolean $noPrefix - удалить префиксы из свойств
     */
    public function ToArray(bool $noPrefix = false) : array
    {
        $data = array();
        foreach ($this->_data as $key => $value) {
            $value = $this->_typeExchange('get', $key);
            if (is_array($value)) {
                foreach ($value as $index => $v) {
                    if ((is_string($v) || is_object($v)) && method_exists($v, 'ToArray')) {
                        $value[$index] = $v->ToArray($noPrefix);
                    }
                }
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
    public function Original()
    {
        return (object)$this->_original;
    }

    /**
     * Возвращает префикс полей обьекта
     *
     * @return string
     */
    public function Prefix()
    {
        return $this->_prefix;
    }

    /**
     * Изменен
     * @return bool 
     */
    public function IsChanged()
    {
        return $this->_changed;
    }

    /**
     * Поле изменено
     * @return bool 
     */
    public function IsPropertyChanged(string $property, bool $dummy = false) : bool
    {

        if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
        }
        if(!isset($this->Original()->$property) && isset($this->_data[$property])) {
            return true;
        }
        else if(!isset($this->_data[$property]) && !isset($this->Original()->$property)) {
            return false;
        }
        else {
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
    public function ToJSON()
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
        return isset($this->_data[$name]);;
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
    public function __get(string $property) : mixed
    {

        $propertyCamelCased = StringHelper::ToCamelCaseVar($property, true, false);
        if(method_exists($this, 'getProperty'.$propertyCamelCased)) {
            $value = $this->{'getProperty'.$propertyCamelCased}();
        }
        else {

            $property = strtolower($property);
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
    public function __set(string $property, mixed $value) : void
    {
        $propertyCamelCased = StringHelper::ToCamelCaseVar($property, true, false);
        if(method_exists($this, 'setProperty'.$propertyCamelCased)) {
            $this->{'setProperty'.$propertyCamelCased}($value);
        }
        else {
            $property = strtolower($property);
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
    protected function _typeExchange(string $mode, string $property, mixed $value = null) : mixed
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
    protected function _typeToData($data)
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
