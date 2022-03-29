<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */
namespace Colibri\Data\Storages\Fields;

use Colibri\Common\DateHelper;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Debug;
use Colibri\Utils\ExtendedObject;
use Colibri\Data\Storages\Storage;

/**
 * Класс представление поля типа обьект
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class ObjectField extends ExtendedObject
{
    /**
     * Поле
     * @var Field
     */
    private $_field;

    /**
     * Хранилище
     * @var Storage
     */
    private $_storage;
    
    /**
     * Конструктор
     * @param string|mixed[string] $data данные
     * @param Storage $storage хранилище
     * @param Field $field поле
     * @return void
     */
    public function __construct(mixed $data, Storage $storage, Field $field)
    {
        parent::__construct(is_string($data) ? (array)json_decode($data) : (array)$data, '', false);
        $this->_storage = $storage;
        $this->_field = $field;
    }
    
    /**
     * Замена типов
     * @param string $mode режим, get или set
     * @param string $property свойство
     * @param mixed $value значение
     * @return mixed результат
     */
    protected function _typeExchange(string $mode, string $property, mixed $value = false) : mixed
    {
        if ($this->_prefix && strstr($property, $this->_prefix) === false) {
            $property = $this->_prefix.$property;
        }

        $rowValue = $mode == 'get' ? (isset($this->_data[$property]) ? $this->_data[$property] : null) : $value;
        $fieldName = $this->_storage->GetFieldName($property);
        
        /** @var Field */
        $field = isset($this->_field->fields->$fieldName) ? $this->_field->fields->$fieldName : false;

        if (!$field) {
            if ($mode == 'get') {
                return $rowValue;
            } else {
                $this->_data[$property] = $rowValue;
                return null;
            }
        }
        
        if ($mode == 'get') {
            if ($field->isLookup) {
                return $field->lookup->Selected(isset($this->_data[$property]) ? $this->_data[$property] : 0);
            } elseif ($field->isValues) {
                if (!$field->multiple) {
                    if(isset($this->_data[$property])) {
                        $v = $field->type == 'numeric' ? (float)$this->_data[$property] : $this->_data[$property];
                        $v = is_array($v) || is_object($v) ? ((array)$v)['value'] : $v;
                        $t = isset($field->values[$v]) ? $field->values[$v] : null;
                        return new ValueField($v, $t);
                    }
                    else {
                        return null;
                    }
                } else {
                    $vv = is_array($this->_data[$property]) ? $this->_data[$property] : explode(',', $this->_data[$property]);
                    $r = array();
                    foreach ($vv as $v) {
                        $r[$v] = new ValueField($v, $this->_values[$v]);
                    }
                    return $r;
                }
            }
        }

        if($field->class === 'string' || !$field->class) {
            if ($mode == 'get') {
                $value = $rowValue;
            } else {
                $this->_data[$property] = $rowValue;
            }
        }
        else if($field->class === 'bool') {
            if ($mode == 'get') {
                $value = (bool)$rowValue;
            } else {
                if ($field->required && is_null($rowValue)) {
                    $this->_data[$property] = false;
                } else {
                    $this->_data[$property] = ((bool)$rowValue) ? 1 : 0;
                }
            }
        }
        else if($field->class === 'int') {
            if ($mode == 'get') {
                $value = $rowValue == "" ? "" : ($rowValue == (float)$rowValue ? (float)$rowValue : $rowValue);
            } else {
                $this->_data[$property] = $field->required ? ($rowValue === "" ? 0 : $rowValue) : ($rowValue === "" ? null : $rowValue);
            }
        }
        else if($field->class === 'array') {
            if ($mode == 'get') {
                $value = $rowValue == "" ? "" : (is_array($rowValue) ? $rowValue : explode(',', $rowValue));
            } else {
                $this->_data[$property] = $field->required ? ($rowValue === "" ? [] : $rowValue) : ($rowValue === "" ? null : $rowValue);
            }
        }
        else {

            
            $class = $field->class;
            if(!class_exists($class)) {
                $class = 'Colibri\\Data\\Storages\\Fields\\'.$class;
            }

            if ($mode == 'get') {
                $this->_data[$property] = $rowValue instanceof $class ? $rowValue : new $class($rowValue, $this->_storage, $field);
                $value = $this->_data[$property];
            } else {

                if($rowValue instanceof $class) {
                    $this->_data[$property] = (string)$rowValue;
                }
                else {
                    $c = new $class($rowValue, $this->_storage, $field);
                    $this->_data[$property] = (string)$c;
                }

            }
            
        }

        return $value;
    }
    
    /**
     * Геттер
     * @param string $prop свойство
     * @return mixed значение
     */
    public function __get(string $prop) : mixed
    {
        if(strtolower($prop) == 'field') {
            return $this->_field;
        }
        else if(strtolower($prop) == 'storage') {
            return $this->_storage;
        }
        return $this->_typeExchange('get', $prop);
    }
    
    /**
     * Сеттер
     * @param string $property свойство
     * @param mixed $value значение
     * @return void 
     */
    public function __set(string $property, mixed $value) : void
    {
        $this->_typeExchange('set', $property, $value);
    }
    
    /**
     * Возвращает в виде строки
     * @return string результат JSON
     */
    public function ToString() : string
    {
        $obj = (object)array();
        foreach ($this->_data as $k => $v) {
            if (is_object($v) && method_exists($v, 'ToArray')) {
                $obj->{$k} = $v->ToArray();
            } else {
                $obj->{$k} = $v;
            }
        }
        return json_encode($obj, \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);
    }

    /**
     * Return string value of this object
     *
     * @return string
     */
    public function __toString() : string {
        return $this->ToString();
    }


    
}


