<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Models
 */

namespace Colibri\Data\Storages\Models;

use Colibri\Common\DateHelper;
use Colibri\Data\Storages\Fields\FileField;
use Colibri\Data\Storages\Storage;
use Colibri\Data\Models\DataModelException;
use Colibri\Data\Models\DataRow as BaseDataRow;
use Colibri\Data\Models\DataTable;
use Colibri\Data\Models\DataCollection;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\Storages\Fields\DateTimeField;
use Colibri\Data\Storages\Fields\Field;
use Colibri\Data\Storages\Fields\ValueField;
use Colibri\Exceptions\ValidationException;
use ReflectionClass;
use Colibri\Data\Storages\Fields\UUIDField;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Data\Storages\Fields\FileListField;

/**
 * Представление строки в таблице в хранилище
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Models
 *
 * @property int $id
 * @property DateTimeField $datecreated
 * @property DateTimeField $datemodified
 *
 */
class DataRow extends BaseDataRow
{
    /**
     * Хранилище
     * @var Storage
     */
    protected $_storage;

    protected static array $casts = [];

    /**
     * Конструктор
     *
     * @param DataTable|DataCollection $table
     * @param mixed $data
     * @param Storage|null $storage
     * @throws DataModelException
     */
    public function __construct(DataTable|DataCollection $table, mixed $data = null, ?Storage $storage = null)
    {
        if (!$storage) {
            throw new DataModelException('Unknown storage');
        }
        $this->_storage = $storage;
        if($storage->accessPoint->dbms === DataAccessPoint::DBMSTypeNoSql) {
            $this->_changeKeyCase = false;
        }

        if (empty($data)) {
            $allowedTypes = $this->Storage()->accessPoint->allowedTypes;
            $timestamp = $allowedTypes['timestamp'];
            $timestampGeneric = 'Colibri\\Data\\Storages\\Fields\\' . $timestamp['generic'];
            $dt = new $timestampGeneric('now');
            $data = [
                $this->_storage->GetRealFieldName('id') => 0,
                $this->_storage->GetRealFieldName('datecreated') => (string)$dt,
                $this->_storage->GetRealFieldName('datemodified') => (string)$dt
            ];
        }
        parent::__construct($table, $data, !$storage->accessPoint->fieldsHasPrefix ? '' : $storage->name);
        $this->_processDefaultValues();
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
                } elseif (in_array($fieldName, ['datecreated', 'datemodified', 'datedeleted'])) {
                    if(is_null($rowValue) || (!is_null($rowValue) && is_numeric($rowValue) && $rowValue === 0)) {
                        return null;
                    }
                    $allowedTypes = $this->Storage()->accessPoint->allowedTypes;
                    $timestamp = 'Colibri\\Data\\Storages\\Fields\\' . $allowedTypes['timestamp']['generic'];
                    return new $timestamp($rowValue);
                }
                return $rowValue;
            } else {
                $this->_data[$property] = $rowValue;
                return null;
            }
        }


        $class = null;
        $casts = static::$casts;
        if(isset($casts[$field->{'name'}])) {
            $class = $casts[$field->{'name'}];
        }

        if($class && enum_exists($class) && !is_null($rowValue)) {
            if($mode == 'get') {
                if(!($rowValue instanceof \UnitEnum)) {
                    $rowValue = $class::from($rowValue);
                }
            } elseif ($rowValue instanceof \UnitEnum) {
                $rowValue = $rowValue->{'value'};
            }
        }


        if ($mode == 'get' && !isset($this->_data[$property])) {
            if ($field->default !== null) {
                $reader = $this->_storage->accessPoint->Query(
                    'select ' . (VariableHelper::IsEmpty($field->default) ?
                        '\'\'' : '\'' . $field->default . '\'') . ' as default_value',
                    ['type' => DataAccessPoint::QueryTypeBigData]
                );
                $rowValue = $reader->Read()->default_value;
            } else {
                $rowValue = null;
            }
        }

        if ($mode === 'get' && !is_object($rowValue)) {
            if ($field->isLookup) {
                return $field->lookup->Selected($rowValue);
            } elseif ($field->isValues) {
                if (!$field->{'multiple'}) {
                    $v = $field->{'type'} == 'numeric' ? (float) $rowValue : $rowValue;
                    $t = $v && isset($field->values[$v]) ? $field->values[$v] : '';
                    if($field->{'class'} === 'ValueField') {
                        return $rowValue ? new ValueField($v, $t) : null;
                    } else {
                        return $v;
                    }
                } else {
                    $vv = is_array($rowValue) ? $rowValue : explode(',', $rowValue);
                    $r = [];
                    foreach ($vv as $v) {
                        if($field->{'class'} === 'ValueField') {
                            $r[$v] = new ValueField($v, $v && isset($field->values[$v]) ? $field->values[$v] : '');
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
                    $this->_data[$property] = $rowValue;
                }
            }
        } elseif ($field->{'class'} === 'int' || $field->{'class'} === 'float' || $field->{'class'} === 'double') {
            if ($mode == 'get') {
                if(!$field->{'required'} && $rowValue === null) {
                    $value = null;
                } else {
                    $value = $rowValue === "" ? "" : ($rowValue == (float) $rowValue ? (float) $rowValue : $rowValue);
                }
            } else {
                $this->_data[$property] = $field->required ?
                    ($rowValue === "" ? 0 : $rowValue) : ($rowValue === "" ? null : $rowValue);
            }
        } elseif ($field->{'class'} === 'uuid') {
            if ($mode == 'get') {
                $this->_data[$property] = $rowValue instanceof UUIDField ? $rowValue : new UUIDField($rowValue);
                $value = $this->_data[$property];
            } else {
                $this->_data[$property] = $value instanceof UUIDField ? $value : new UUIDField($value);
            }
        } elseif ($field->{'class'} === 'array' || $field->{'class'} === 'object') {
            if ($mode == 'get') {
                $this->_data[$property] = is_string($rowValue) ? json_decode($rowValue) : $rowValue;
                $value = $this->_data[$property];
            } else {
                $this->_data[$property] = is_string($rowValue) ? $value : json_encode($value);
            }
        } else {

            $class = $class ?: $storage->GetFieldClass($field);

            if ($mode == 'get') {
                try {
                    if(is_null($rowValue)) {
                        return null;
                    } else {
                        $reflection = new ReflectionClass($class);
                        if ($reflection->isSubclassOf(BaseDataRow::class)) {
                            $this->_data[$property] = $rowValue instanceof $class ?
                                $rowValue : $class::Create($rowValue);
                        } else {
                            $this->_data[$property] = $rowValue instanceof $class ?
                                $rowValue : new $class($rowValue, $this->Storage(), $field, $this);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->_data[$property] = $rowValue;
                }
                $value = $this->_data[$property];
            } else {

                if ($rowValue instanceof $class) {
                    if ($field->isLookup) {
                        $valueField = $field->lookup->GetValueField();
                        if(!$valueField) {
                            $this->_data[$property] = (string) $rowValue;
                        } else {
                            $this->_data[$property] = $rowValue?->$valueField ?? null;
                        }
                    } else {
                        $this->_data[$property] = (string) $rowValue;
                    }

                } elseif (!is_null($rowValue)) {

                    try {
                        if ($field->isLookup) {
                            $c = $field->lookup->Selected($rowValue);
                            $valueField = $field->lookup->GetValueField();
                            if($valueField) {
                                $this->_data[$property] = $c?->$valueField ?? null;
                            } else {
                                $this->_data[$property] = (string) $c;
                            }
                        } else {
                            $reflection = new ReflectionClass($class);
                            if($reflection->isEnum()) {
                                $c = $class::from($rowValue);
                                $this->_data[$property] = $c->value;
                            } elseif ($reflection->isSubclassOf(BaseDataRow::class)) {
                                $c = $class::Create($rowValue);
                                $this->_data[$property] = (string) $c;
                            } else {
                                $c = new $class($rowValue, $this->_storage, $field, $this);
                                $this->_data[$property] = (string) $c;
                            }
                        }
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
    protected function _typeToData(mixed $data): mixed
    {

        $storage = $this->Storage();
        foreach ($data as $key => $value) {

            $fieldName = $storage->GetFieldName($key);

            /** @var Field */
            $field = $storage->fields->$fieldName ?? null;
            if ($field && !$field->virtual) {

                if($value instanceof \UnitEnum) {
                    $value = $value->{'value'};
                }

                // подбираем значение по умолчанию
                if (is_null($value) && !is_null($field->default)) {
                    if ($field->{'type'} === 'json' && strstr($field->default, 'json_array') !== false) {
                        $value = '[]';
                    } elseif ($field->{'type'} === 'json' && strstr($field->default, 'json_object') !== false) {
                        $value = '{}';
                    } else {
                        $value = $field->default;
                    }
                }

                if ($field->{'class'} === 'string') {
                    $data[$key] = str_replace("\r\n", "\n", (string) $value);
                } elseif ($field->{'class'} == 'bool') {
                    if (in_array($value, [true, '1', 1, 'true'])) {
                        $data[$key] = true;
                    } elseif (in_array($value, [false, '0', 0, 'false'])) {
                        $data[$key] = false;
                    } else {
                        $data[$key] = null;
                    }
                } elseif ($field->{'class'} == 'int') {
                    $data[$key] = !is_numeric($value) ? null : (int) $value;
                } elseif ($field->{'class'} == 'float') {
                    $data[$key] = !is_numeric($value) ? null : (float) $value;
                } elseif ($field->{'class'} == 'uuid') {
                    $data[$key] = $value instanceof UUIDField ? $value->binary : $value;
                } elseif ($field->{'class'} === 'array') {
                    $data[$key] = is_string($value) ? $value : json_encode($value);
                } else {
                    $data[$key] = is_null($value) ? null : (string) $value;
                }

                if ($field->required && is_null($value)) {
                    throw new ValidationException('The ' . $key .
                        ' field is required for storage ' . $storage->name, 500, null);
                }

            } elseif(!in_array($key, ['datecreated', 'datemodified', 'datedeleted','id'])) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function _processDefaultValues(): bool
    {
        foreach ($this->_storage->fields as $fieldName => $field) {
            $realFieldName = $this->_storage->GetRealFieldName($fieldName);
            if (!is_null($field->default) && !isset($this->_data[$realFieldName])) {
                $this->__set($realFieldName, $field->default);
            }
        }
        return true;
    }

    public function GetValidationData(): mixed
    {
        $storage = $this->Storage();

        $return = [];
        $return['id'] = (int) $this->id;
        $return['datecreated'] = (string) $this->datecreated;
        $return['datemodified'] = (string) $this->datemodified;

        $fields = $storage->fields;
        foreach ($fields as $fieldName => $fieldData) {
            
            if($fieldData->virtual === true) {
                continue;
            }
            /** @var Field $fieldData */
            $fieldValue = $this->$fieldName;
            if (is_null($fieldValue)) {
                $return[$fieldName] = null;
                continue;
            }
            if($fieldValue instanceof \UnitEnum) {
                $fieldValue = $fieldValue->{'value'};
            }
            if ($fieldData->isLookup) {
                if (is_array($fieldValue)) {
                    $ret = [];
                    foreach ($fieldValue as $value) {
                        if (is_object($value) && method_exists($value, 'GetValidationData')) {
                            $ret[] = $value->GetValidationData();
                        } else {
                            $ret[] = $value->{$fieldData->lookup->GetValueField() ?: 'id'};
                        }
                    }
                    $return[$fieldName] = $ret;
                } else {
                    if (is_object($fieldValue)) {
                        if(method_exists($fieldValue, 'GetValidationData')) {
                            if($fieldData->{'class'} === 'string') {
                                $ret = (string)$fieldValue->GetValidationData()->{$fieldData->lookup->GetValueField() ?: 'id'};
                            } elseif($fieldData->{'class'} === 'float') {
                                $ret = (float)$fieldValue->GetValidationData()->{$fieldData->lookup->GetValueField() ?: 'id'};
                            } elseif($fieldData->{'class'} === 'int') {
                                $ret = (int)$fieldValue->GetValidationData()->{$fieldData->lookup->GetValueField() ?: 'id'};
                            } else {
                                $ret = $fieldValue->GetValidationData();
                            }
                        } else {
                            if($fieldData->{'type'} === 'json') {
                                $ret = $fieldValue;
                            }
                        }
                        $return[$fieldName] = $ret;
                    } else {
                        $return[$fieldName] = $fieldValue->{$fieldData->lookup->GetValueField() ?: 'id'};
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
            } elseif ($fieldData->{'class'} === 'object') {
                $return[$fieldName] = (object) $fieldValue;
            } elseif (strstr($fieldData->{'class'}, 'ValueField') !== false) {
                $type = $fieldData->{'type'};
                if (in_array($type, ['int', 'float', 'double', 'decimal', 'uint', 'bigint', 'int2', 'int4', 'int8', 'float4', 'float8'])) {
                    $return[$fieldName] = (float) ((string) $fieldValue);
                } else {
                    $return[$fieldName] = (string) $fieldValue;
                }
            } elseif (strstr($fieldData->{'class'}, 'UUIDField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif (strstr($fieldData->{'class'}, 'DateField') !== false ||
                strstr($fieldData->{'class'}, 'DateTimeField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif ((is_object($fieldValue)) && method_exists($fieldValue, 'GetValidationData')) {
                $return[$fieldName] = $fieldValue->GetValidationData();
            }
        }

        return (object) $return;
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
    public function ToArray(bool $noPrefix = false, ?\Closure $callback = null): array
    {
        $ar = parent::ToArray($noPrefix, $callback);
        foreach ($ar as $key => $value) {
            if ($value instanceof FileField) {
                $ar[$key] = $value->Source();
            } elseif ($value instanceof FileListField) {
                $ar[$key] = (string)$value;
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
        return (string)$this->id;
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

        $originalValue = ($original->$property ?? null);
        $newValue = ($data[$property] ?? null);

        if (is_string($newValue) && StringHelper::IsJsonString($newValue)) {
            $newValue = json_decode($newValue);
        }

        if(is_string($originalValue) && StringHelper::IsJsonString($originalValue)) {
            $originalValue = json_decode($originalValue);
        }

        return $originalValue != $newValue;
    }

    /**
     * Вызывает SaveRow у таблицы
     * @return QueryInfo|bool
     */
    public function Save(bool $performValidationBeforeSave = false): QueryInfo|ICommandResult|bool
    {
        if ($performValidationBeforeSave) {
            $this->Validate(true);
        }
        $return = $this->table->SaveRow($this);
        $this->_processDefaultValues();
        return $return;
    }

    public function Delete(): QueryInfo|ICommandResult|bool
    {
        $params = (object)$this->_storage?->{'params'};
        if($params?->{'softdeletes'} === true) {
            $return = $this->_storage->accessPoint->Update(
                $this->_storage->table,
                [$this->_storage->name . '_datedeleted' => DateHelper::ToDbString()],
                $this->_storage->name . '_id=' . $this->id
            );
            if(!$return?->error) {
                return true;
            }
            return $return;
        } else {
            return $this->table->DeleteRow($this);
        }
    }

    public function Restore(): QueryInfo|ICommandResult|bool
    {
        $params = (object)$this->_storage?->{'params'};
        if($params?->{'softdeletes'} === true) {
            $return = $this->_storage->accessPoint->Update(
                $this->_storage->table,
                [$this->_storage->name . '_datedeleted' => null],
                $this->_storage->name . '_id=' . $this->id
            );
            $this->datedeleted = null;
            if(!$return?->error) {
                return true;
            }
            return $return;
        } else {
            return false;
        }
    }

    public function Changed(bool $returnAll = false): array
    {
        $data = $this->GetData();
        if($this->_storage->accessPoint->hasAutoincrement) {
            unset($data[$this->Storage()->GetRealFieldName('id')]);
        }
        foreach ($data as $key => $value) {
            if (!$returnAll && !$this->IsPropertyChanged($key)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    public function DataToChange(bool $saveAll = false): ?array
    {
        $data = $this->Changed($saveAll);
        if(empty($data)) {
            // nothing to save
            return null;
        }

        $allowedTypes = $this->Storage()->accessPoint->allowedTypes;

        $params = [];
        $fieldValues = [];
        foreach ($data as $key => $value) {
            $fieldName = $this->_storage->GetFieldName($key);
            if($fieldName === 'id') {
                $params[$key] = $value;
                $fieldValues[$key] = '[[id:integer]]';
                continue;
            }
            /** @var \Colibri\Data\Storages\Fields\Field $field */
            $field = $this->_storage->fields->$fieldName ?? null;
            $className = $field ? $field?->{'class'} : 'string';
            $allowedType = $allowedTypes[$field?->{'type'}] ?? null;
            $paramType = $allowedType['param'] ?? null;
            $convert = $allowedType['convert'] ?? null;
            if(!$paramType) {
                $paramType = 'string';
                if ($field && in_array($field->{'type'}, ['blob', 'tinyblob', 'longblob'])) {
                    $paramType = 'blob';
                } elseif ($field && in_array($className, [
                    'int'
                ])) {
                    $paramType = 'integer';
                } elseif ($field && in_array($className, ['float'])) {
                    $paramType = 'double';
                } elseif ($field && in_array($className, ['bool'])) {
                    $paramType = 'integer';
                    $value = $value === true ? 1 : 0;
                } else {
                    $className = 'Colibri\\Data\\Storages\\Fields\\' . $className;
                    if(method_exists($className, 'ParamTypeName')) {
                        eval('$paramType = ' . $className . '::ParamTypeName();');
                    }
                }
            }
            if($convert) {
                eval('$convert = ' . $convert . ';');
                $value = $convert($value);
            }

            $params[$key] = $value;
            $fieldValues[$key] = '[[' . $key . ':' . $paramType . ']]';
        }

        $timestamp = $allowedTypes['timestamp'];
        $timestampGeneric = 'Colibri\\Data\\Storages\\Fields\\' . $timestamp['generic'];
        $now = new $timestampGeneric('now');
        $timestampType = 'string';
        if(method_exists($timestampGeneric, 'ParamTypeName')) {
            eval('$timestampType = ' . $timestampGeneric . '::ParamTypeName();');
        }

        // set the modified date
        $idm = $this->_storage->GetRealFieldName('datemodified');
        $fieldValues[$idm] = '[[' . $idm . ':' . $timestampType . ']]';
        $params[$idm] = (string)$now;
        if($saveAll) {
            // update created date
            $idc = $this->_storage->GetRealFieldName('datecreated');
            $fieldValues[$idc] = '[[' . $idc . ':' . $timestampType . ']]';
            $params[$idc] = (string)$now;
        }

        return [$fieldValues, $params];

    }

}
