<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */
namespace Colibri\Data\Storages\Fields;

use Colibri\Utils\ExtendedObject;
use Colibri\Data\Storages\Storage;
use Colibri\Data\Storages\Models\DataRow;
use ReflectionClass;

/**
 * Класс представление поля типа обьект
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class ObjectField extends ExtendedObject
{

    protected static array $casts = [];

    protected ? ExtendedObject $_datarow = null;

    /**
     * Поле
     * @var Field
     */
    protected ? Field $_field = null;

    /**
     * Хранилище
     * @var Storage
     */
    protected ? Storage $_storage = null;

    /**
     * Конструктор
     * @param string|mixed[string] $data данные
     * @param Storage $storage хранилище
     * @param Field $field поле
     * @return void
     */
    public function __construct(mixed $data, ? Storage $storage = null, ? Field $field = null, ExtendedObject $datarow = null)
    {
        parent::__construct(is_string($data) ? (array) json_decode($data) : (array) $data, '', false);
        $this->_storage = $storage;
        $this->_field = $field;
        $this->_datarow = $datarow;
    }

    /**
     * Замена типов
     * @param string $mode режим, get или set
     * @param string $property свойство
     * @param mixed $value значение
     * @return mixed результат
     */
    protected function _typeExchange(string $mode, string $property, mixed $value = false): mixed
    {
        if ($this->_prefix && strstr($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
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
                if (!$field->{'multiple'}) {
                    if (isset($this->_data[$property])) {
                        $v = $field->{'type'} == 'numeric' ? (float) $this->_data[$property] : $this->_data[$property];
                        $v = is_array($v) || is_object($v) ? ((array) $v)['value'] : $v;
                        $t = isset($field->values[$v]) ? $field->values[$v] : '';
                        if($field->{'class'} === 'ValueField') {
                            return new ValueField($v, $t);
                        } else {
                            return $v;
                        }
                    } else {
                        return null;
                    }
                } else {
                    $vv = is_array($this->_data[$property]) ? $this->_data[$property] : explode(',', $this->_data[$property]);
                    $r = array();
                    foreach ($vv as $v) {
                        if($field->{'class'} === 'ValueField') {
                            $r[$v] = new ValueField($v, $this->_values[$v]);
                        } else {
                            $r[$v] = $v;
                        }
                    }
                    return $r;
                }
            }
        }

        if ($field->{'class'} === 'string' || !$field->{'class'}) {
            if ($mode == 'get') {
                $value = $rowValue;
            } else {
                $this->_data[$property] = $rowValue;
            }
        } elseif ($field->{'class'} === 'bool') {
            if ($mode == 'get') {
                $value = (bool) $rowValue;
            } else {
                if ($field->required && is_null($rowValue)) {
                    $this->_data[$property] = false;
                } else {
                    $this->_data[$property] = ((bool) $rowValue) ? 1 : 0;
                }
            }
        } elseif ($field->{'class'} === 'int' || $field->{'class'} === 'float' || $field->{'class'} === 'double') {
            if ($mode == 'get') {
                $value = $rowValue == "" ? "" : ($rowValue == (float) $rowValue ? (float) $rowValue : $rowValue);
            } else {
                $this->_data[$property] = $field->required ? ($rowValue === "" ? 0 : $rowValue) : ($rowValue === "" ? null : $rowValue);
            }
        } elseif ($field->{'class'} === 'array') {
            if ($mode == 'get') {
                $value = $rowValue == "" ? "" : (is_array($rowValue) ? $rowValue : explode(',', $rowValue));
            } else {
                $this->_data[$property] = $field->required ? ($rowValue === "" ? [] : $rowValue) : ($rowValue === "" ? null : $rowValue);
            }
        } else {


            $class = $this->_storage->GetFieldClass($field);

            if ($mode == 'get') {
                try {
                    if (is_null($rowValue)) {
                        return $rowValue;
                    }

                    $reflection = new ReflectionClass($class);
                    if ($reflection->isSubclassOf(DataRow::class)) {
                        $this->_data[$property] = $rowValue instanceof $class ? $rowValue : $class::Create($rowValue);
                    } else {
                        $this->_data[$property] = $rowValue instanceof $class ? $rowValue : new $class($rowValue, $this->Storage(), $field, $this);
                    }

                } catch (\Throwable $e) {
                    $this->_data[$property] = $rowValue;
                }
                $value = $this->_data[$property];
            } else {
                try {
                    if ($rowValue instanceof $class) {
                        $this->_data[$property] = $rowValue;
                    } else {
                        $c = new $class($rowValue, $this->_storage, $field);
                        $this->_data[$property] = $c;
                    }
                } catch (\Throwable $e) {
                    $this->_data[$property] = $rowValue;
                }
            }

        }

        return $value;
    }

    public function GetValidationData(): mixed
    {

        $return = [];

        $fields = $this->_field->fields;
        foreach ($fields as $fieldName => $fieldData) {
            /** @var Field $fieldData */
            $fieldValue = $this->$fieldName;
            if (is_null($fieldValue)) {
                $return[$fieldName] = null;
                continue;
            }
            if ($fieldData->isLookup) {
                if (is_array($fieldValue)) {
                    $ret = [];
                    foreach ($fieldValue as $value) {
                        if (is_object($value) && method_exists($value, 'GetValidationData')) {
                            $ret[] = $value->GetValidationData();
                        } else {
                            $ret[] = $value->{$fieldData->lookup->GetValueField()};
                        }
                    }
                    $return[$fieldName] = $ret;
                } else {
                    if (is_object($fieldValue) && method_exists($fieldValue, 'GetValidationData')) {
                        $return[$fieldName] = $fieldValue->GetValidationData();
                    } else {
                        $return[$fieldName] = $fieldValue->{$fieldData->lookup->GetValueField()};
                    }
                }
            } elseif ($fieldData->{'class'} === 'string') {
                $return[$fieldName] = (string) $fieldValue;
            } elseif ($fieldData->{'class'} === 'int') {
                $return[$fieldName] = (int) $fieldValue;
            } elseif ($fieldData->{'class'} === 'float') {
                $return[$fieldName] = (float) $fieldValue;
            } elseif ($fieldData->{'class'} === 'bool') {
                $return[$fieldName] = (bool) $fieldValue;
            } elseif ($fieldData->{'class'} === 'array') {
                $return[$fieldName] = (array) $fieldValue;
            } elseif (strstr($fieldData->{'class'}, 'ValueField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif (strstr($fieldData->{'class'}, 'UUIDField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif (strstr($fieldData->{'class'}, 'DateField') !== false || strstr($fieldData->{'class'}, 'DateTimeField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif (method_exists($fieldValue, 'GetValidationData')) {
                $return[$fieldName] = $fieldValue->GetValidationData();
            }
        }

        return (object) $return;
    }

    /**
     * Геттер
     * @param string $prop свойство
     * @return mixed значение
     */
    public function __get(string $prop): mixed
    {
        return $this->_typeExchange('get', $prop);
    }

    public function Field(): Field
    {
        return $this->_field;
    }

    public function Storage(): Storage
    {
        return $this->_storage;
    }

    /**
     * Сеттер
     * @param string $property свойство
     * @param mixed $value значение
     * @return void 
     */
    public function __set(string $property, mixed $value): void
    {
        $this->_typeExchange('set', $property, $value);
    }

    /**
     * Возвращает в виде строки
     * @return string результат JSON
     */
    public function ToString(): string
    {
        $obj = (object) [];
        foreach ($this->_data as $k => $v) {
            if (is_object($v) && get_class($v) === ValueField::class) {
                $obj->{$k} = (string) $v;
            } elseif (is_object($v) && method_exists($v, 'ToArray')) {
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
    public function __toString(): string
    {
        return $this->ToString();
    }

    public function ToArray(bool $noPrefix = false): array
    {
        $newArray = [];
        foreach($this as $key => $value) {
            
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
            } elseif (is_object($value) && $value instanceof ValueField) {
                $value = (string) $value;
            } elseif (is_object($value) && method_exists($value, 'ToArray')) {
                $value = $value->ToArray($noPrefix);
            }

            $newArray[$key] = $value;
        }
        return $newArray;
    }

}