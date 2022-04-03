<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Models
 */
namespace Colibri\Data\Storages\Models;

use Colibri\Data\Storages\Fields\ArrayField;
use Colibri\Data\Storages\Fields\FileField;
use Colibri\Data\Storages\Fields\FileListField;
use Colibri\Data\Storages\Fields\ObjectField;
use Colibri\Data\Storages\Storage;
use Colibri\Data\Models\DataModelException;
use Colibri\Data\Models\DataRow as BaseDataRow;
use Colibri\Data\Models\DataTable;
use Colibri\Common\DateHelper;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\Storages\Fields\DateTimeField;
use Colibri\Data\Storages\Fields\Field;
use Colibri\Data\Storages\Fields\ValueField;
use ReflectionClass;
use Colibri\Data\Storages\Fields\UUIDField;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\Storages\Fields\RemoteFileField;
use Colibri\Data\SqlClient\NonQueryInfo;

/**
 * Представление строки в таблице в хранилище
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Models
 */
class DataRow extends BaseDataRow
{
    /**
     * Хранилище
     * @var Storage
     */
    protected $_storage;
    
    /**
     * Конструктор
     *
     * @param DataTable $table
     * @param mixed $data
     * @param Storage|null $storage
     * @throws DataModelException
     */
    public function __construct(DataTable $table, mixed $data = null, ?Storage $storage = null)
    {
        if(!$storage) {
            throw new DataModelException('Unknown storage');
        }
        $this->_storage = $storage;
        parent::__construct($table, $data, $storage->name);
    }

    /**
     * Заполняет строку из обьекта
     * @param mixed $obj обьект или массив
     * @return void 
     */
    public function Fill(mixed $obj) : void
    {
        $obj = (object)$obj;
        if (!is_null($obj)) {
            foreach ($this->Storage()->fields as $field) {
                $f = $this->Storage()->GetRealFieldName($field->name);
                if (!isset($obj->$f)) {
                    $f = $field->name;
                    if (!isset($obj->$f)) {
                        continue;
                    }
                }
                if (!is_object($obj->$f) && $obj->$f === "{null}") {
                    $this->_data[$f] = null;
                } else {
                    $this->_typeExchange('set', $f, $obj->$f);
                }
            }
        }
    }
    
    /**
     * Конвертация типов
     * @param string $mode режим, get или сет
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
        $fieldName = $this->Storage()->GetFieldName($property);
        
        $storage = $this->Storage();
        /** @var Field */
        $field = isset($storage->fields->$fieldName) ? $storage->fields->$fieldName : false;
        
        if (!$field) {
            if ($mode == 'get') {
                if($fieldName == 'id') {
                    return (int)$rowValue;
                }
                else if(in_array($fieldName, ['datecreated', 'datemodified'])) {
                    return new DateTimeField($rowValue);
                }
                return $rowValue;
            } else {
                $this->_data[$property] = $rowValue;
                return null;
            }
        }

        if($mode == 'get' && !isset($this->_data[$property])) {
            if($field->default !== null) {
                $reader = $this->_storage->accessPoint->Query('select '.$field->default.' as default_value', ['type' => DataAccessPoint::QueryTypeReader]);
                $rowValue = $reader->Read()->default_value;    
            }
            else {
                $rowValue = null;
            }
        }
        
        if ($mode == 'get') {
            if ($field->isLookup) {
                return $field->lookup->Selected($rowValue);
            } elseif ($field->isValues) {
                if (!$field->multiple) {
                    $v = $field->type == 'numeric' ? (float)$rowValue : $rowValue;
                    $t = $field->values[$v];
                    return new ValueField($v, $t);
                } else {
                    $vv = is_array($rowValue) ? $rowValue : explode(',', $rowValue);
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
        else if($field->class === 'uuid') {
            if ($mode == 'get') {
                $this->_data[$property] = $rowValue instanceof UUIDField ? $rowValue : new UUIDField($rowValue);
                $value = $this->_data[$property];
            } else {
                $this->_data[$property] = $value instanceof UUIDField ? $value : new UUIDField($value);
            }
        }
        else {

            $class = $field->class;
            if(!class_exists($class)) {
                $class = 'Colibri\\Data\\Storages\\Fields\\'.$class;
            }

            if ($mode == 'get') {
                $this->_data[$property] = $rowValue instanceof $class ? $rowValue : new $class($rowValue, $this->Storage(), $field);
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
     * Конвертирует данные для передачи через функцию GetData
     * @param mixed $data данные для конвертации
     * @return mixed сконвертированные данные
     */
    protected function _typeToData(mixed $data) : mixed {

        foreach ($data as $k => $v) {
            $storage = $this->Storage();
            $kk = $storage->GetFieldName($k);
            /** @var Field */
            $field = isset($storage->fields->$kk) ? $storage->fields->$kk : false;
            if ($field) {
                
                if($field->class === 'string') {
                    if (!is_object($v)) {
                        $data[$k] = str_replace("\r\n", "\n", $v);
                    } else {
                        $data[$k] = $v;
                    }
                }
                else if($field->class == 'bool') {
                    if(is_null($v)) {
                        if (!is_null($field->default)) {
                            $v = $field->default;
                        } else {
                            $v = null;
                        }
                    }
                    
                    if($v === true || $v === '1' || $v === 'true') {
                        $v = 1;
                    }
                    else if($v === false || $v === '0' || $v === 'false') {
                        $v = 0;
                    }

                }
                else if($field->class == 'int') {
                    if (is_null($v)) {
                        if (!is_null($field->default)) {
                            $v = $field->default;
                        } else {
                            $v = null;
                        }
                    }
                    if ($v === "") {
                        $v = $field->required ? 0 : null;
                    }
                    $data[$k] = $v;
                }
                else if($field->class == 'uuid') {

                    if (is_null($v)) {
                        if (!is_null($field->default)) {
                            $v = $field->default;
                        } else {
                            $v = null;
                        }
                    }
                    
                    if ($v === "") {
                        $v = $field->required ? 0 : null;
                    }

                    $data[$k] = $v instanceof UUIDField ? $v->binary : $v;
                }
                else {
                    $data[$k] = (string)$v;
                }

            }
        }
        return $data;
    }
    
    /**
     * Возвращает хранилище
     * @return Storage 
     */
    public function Storage() : Storage
    {
        return $this->_storage;
    }
    
    /**
     * Возвращает строку в виде массива
     * @param bool $noPrefix да - возвращать без префиксами
     * @return array 
     */
    public function ToArray(bool $noPrefix = false) : array
    {
        $ar = parent::ToArray($noPrefix);
        foreach ($ar as $key => $value) {
            if ($value instanceof FileField) {
                $ar[$key] = $value->Source();
            }   
        }
        return $ar;
    }

    /**
     * Конвертирует в строку
     * @return string
     */
    public function ToString() : string 
    {
        $string = array();
        foreach ($this->Storage()->fields as $field) {
            $value = $this->{$field->name};
            
            if(VariableHelper::IsObject($value)) {
                $ref = new ReflectionClass($value);
                $hasToString = $ref->hasMethod('ToString');

                if ($hasToString) {
                    $value = $value->ToString();
                }
            }
            else {
                $string[] = (string)$value;
            }
            $string[] = StringHelper::StripHTML($value);
        }
        
        $string = implode(' ', $string);
        $string = str_replace(array("\n", "\r", "\t"), " ", $string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace('  ', ' ', $string);
        return preg_replace('/\s\s+/', ' ', $string);
    }


    public function __toString() : string 
    {
        return $this->id;
    }

    /**
     * Поле изменено
     * @return bool 
     */
    public function IsPropertyChanged(string $property, bool $convertData = false) : bool
    {

        if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
        }

        $data = $this->GetData();

        return $this->Original()->$property != $data[$property];
    }

    /**
     * Вызывает SaveRow у таблицы
     * @return bool 
     */
    public function Save() : bool
    {
        return $this->table->SaveRow($this);
    }

    public function Delete(): NonQueryInfo
    {
        return $this->table->DeleteRow($this);
    }
    
}


