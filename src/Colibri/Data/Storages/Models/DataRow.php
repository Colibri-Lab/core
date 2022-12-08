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
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\App;
use Colibri\AppException;

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
    public function __construct(DataTable $table, mixed $data = null, ? Storage $storage = null)
    {
        if (!$storage) {
            throw new DataModelException('Unknown storage');
        }
        $this->_storage = $storage;

        if(empty($data)) {
            $dt = new \DateTime();
            $data = [
                $this->_storage->GetRealFieldName('id') => 0, 
                $this->_storage->GetRealFieldName('datecreated') => $dt->format('Y-m-d H:i:s'), 
                $this->_storage->GetRealFieldName('datemodified') => $dt->format('Y-m-d H:i:s')
            ];
        }
        parent::__construct($table, $data, $storage->name);
    }

    /**
     * Заполняет строку из обьекта
     * @param mixed $obj обьект или массив
     * @return void 
     */
    public function Fill(mixed $obj): void
    {
        $obj = (object) $obj;
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
    protected function _typeExchange(string $mode, string $property, mixed $value = false): mixed
    {

        if ($this->_prefix && strstr($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
        }

        $rowValue = $mode == 'get' ? (isset($this->_data[$property]) ? $this->_data[$property] : null) : $value;
        $fieldName = $this->Storage()->GetFieldName($property);

        $storage = $this->Storage();
        /** @var Field */
        $field = isset($storage->fields->$fieldName) ? $storage->fields->$fieldName : false;

        if (!$field) {
            if ($mode == 'get') {
                if ($fieldName == 'id') {
                    return (int) $rowValue;
                } elseif (in_array($fieldName, ['datecreated', 'datemodified'])) {
                    return new DateTimeField($rowValue);
                }
                return $rowValue;
            } else {
                $this->_data[$property] = $rowValue;
                return null;
            }
        }

        if ($mode == 'get' && !isset($this->_data[$property])) {
            if ($field->default !== null) {
                $reader = $this->_storage->accessPoint->Query('select ' . (empty($field->default) ? '\'\'' : $field->default) . ' as default_value', ['type' => DataAccessPoint::QueryTypeBigData]);
                $rowValue = $reader->Read()->default_value;
            } else {
                $rowValue = null;
            }
        }

        if ($mode == 'get') {
            if ($field->isLookup) {
                return $field->lookup->Selected($rowValue);
            } elseif ($field->isValues) {
                if (!$field->multiple) {
                    $v = $field->type == 'numeric' ? (float) $rowValue : $rowValue;
                    $t = $v && isset($field->values[$v]) ? $field->values[$v] : '';
                    return $rowValue ? new ValueField($v, $t) : null;
                } else {
                    $vv = is_array($rowValue) ? $rowValue : explode(',', $rowValue);
                    $r = array();
                    foreach ($vv as $v) {
                        $r[$v] = new ValueField($v, $v && isset($this->_values[$v]) ? $this->_values[$v] : '');
                    }
                    return $r;
                }
            }
        }

        if ($field->class === 'string' || !$field->class) {
            if ($mode == 'get') {
                $value = $rowValue;
            } else {
                $this->_data[$property] = $rowValue;
            }
        } elseif ($field->class === 'bool') {
            if ($mode == 'get') {
                $value = (bool) $rowValue;
            } else {
                if ($field->required && is_null($rowValue)) {
                    $this->_data[$property] = false;
                } else {
                    $this->_data[$property] = $rowValue;
                }
            }
        } elseif ($field->class === 'int' || $field->class === 'float' || $field->class === 'double') {
            if ($mode == 'get') {
                $value = $rowValue == "" ? "" : ($rowValue == (float) $rowValue ? (float) $rowValue : $rowValue);
            } else {
                $this->_data[$property] = $field->required ? ($rowValue === "" ? 0 : $rowValue) : ($rowValue === "" ? null : $rowValue);
            }
        } elseif ($field->class === 'uuid') {
            if ($mode == 'get') {
                $this->_data[$property] = $rowValue instanceof UUIDField ? $rowValue : new UUIDField($rowValue);
                $value = $this->_data[$property];
            } else {
                $this->_data[$property] = $value instanceof UUIDField ? $value : new UUIDField($value);
            }
        } elseif ($field->class === 'array') {
            if ($mode == 'get') {
                $this->_data[$property] = is_string($rowValue) ? json_decode($rowValue) : $rowValue;
                $value = $this->_data[$property];
            } else {
                $this->_data[$property] = is_string($rowValue) ? $value : json_encode($value);
            }
        } else {

            $class = $storage->GetFieldClass($field);

            if ($mode == 'get') {
                try {
                    $this->_data[$property] = $rowValue instanceof $class ? $rowValue : new $class($rowValue, $this->Storage(), $field, $this);
                } catch (\Throwable $e) {
                    $this->_data[$property] = $rowValue;
                }
                $value = $this->_data[$property];
            } else {

                if ($rowValue instanceof $class) {
                    $this->_data[$property] = (string) $rowValue;
                } elseif (!is_null($rowValue)) {

                    try {
                        $c = new $class($rowValue, $this->_storage, $field, $this);
                        $this->_data[$property] = (string) $c;
                    } catch (\Throwable $e) {
                        $this->_data[$property] = $rowValue;
                    }

                } else {
                    $this->_data[$property] = null;
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
    protected function _typeToData(mixed $data, bool $noPrefix = false): mixed
    {

        $storage = $this->Storage();
        foreach ($data as $k => $v) {
            $kk = $storage->GetFieldName($k);
            /** @var Field */
            $field = isset($storage->fields->$kk) ? $storage->fields->$kk : false;

            if ($field) {

                if ($field->class === 'string') {
                    if (!is_object($v)) {
                        $data[$k] = str_replace("\r\n", "\n", $v);
                    } else {
                        $data[$k] = $v;
                    }
                } elseif ($field->class == 'bool') {
                    if (is_null($v)) {
                        if (!is_null($field->default)) {
                            $v = $field->default;
                        } else {
                            $v = null;
                        }
                    }

                    if ($v === true || $v === '1' || $v === 1 || $v === 'true') {
                        $v = true;
                    } elseif ($v === false || $v === '0' || $v === 0 || $v === 'false') {
                        $v = false;
                    }
                    
                    $data[$k] = $v;

                } elseif ($field->class == 'int') {
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
                } elseif ($field->class == 'uuid') {

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
                } elseif ($field->class === 'array') {
                    $data[$k] = is_string($v) ? $v : json_encode($v);
                } else {
                    $data[$k] = is_null($v) ? null : (string) $v;
                }

            }
        }
        
        if($noPrefix) {
            $newData = [];
            foreach($data as $key => $value) {
                $newData[$storage->GetFieldName($key)] = $value;
            }
            $data = $newData;
        }
        


        return $data;
    }

    /**
     * Возвращает хранилище
     * @return Storage 
     */
    public function Storage(): Storage
    {
        return $this->_storage;
    }

    /**
     * Возвращает строку в виде массива
     * @param bool $noPrefix да - возвращать без префиксами
     * @return array 
     */
    public function ToArray(bool $noPrefix = false): array
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
    public function ToString(): string
    {
        $string = array();
        foreach ($this->Storage()->fields as $field) {
            $value = $this->{$field->name};

            if (VariableHelper::IsObject($value)) {
                $ref = new ReflectionClass($value);
                $hasToString = $ref->hasMethod('ToString');

                if ($hasToString) {
                    $value = $value->ToString();
                }
            } else {
                $string[] = (string) $value;
            }
            $string[] = StringHelper::StripHTML($value ?: '');
        }

        $string = implode(' ', $string);
        $string = str_replace(array("\n", "\r", "\t"), " ", $string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace('  ', ' ', $string);
        return preg_replace('/\s\s+/', ' ', $string);
    }


    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * Поле изменено
     * @return bool 
     */
    public function IsPropertyChanged(string $property, bool $convertData = false): bool
    {

        if (!empty($this->_prefix) && strpos($property, $this->_prefix) === false) {
            $property = $this->_prefix . $property;
        }

        $data = $this->GetData();
        $original = $this->Original();

        return ($original->$property ?? null) != ($data[$property] ?? null);
    }

    /**
     * Вызывает SaveRow у таблицы
     * @return QueryInfo|bool 
     */
    public function Save(): QueryInfo|bool
    {
        return $this->table->SaveRow($this);
    }

    public function Delete(): QueryInfo
    {
        return $this->table->DeleteRow($this);
    }

}
