<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Models
 */
namespace Colibri\Data\Storages\Models;

use App\Modules\EcoloPlace\Models\Credit;
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
use Colibri\Exceptions\ValidationException;
use Colibri\Utils\ExtendedObject;
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
                $reader = $this->_storage->accessPoint->Query('select ' . (VariableHelper::IsEmpty($field->default) ? '\'\'' : '\''.$field->default.'\'') . ' as default_value', ['type' => DataAccessPoint::QueryTypeBigData]);
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
                    $reflection = new ReflectionClass($class);
                    if($reflection->isSubclassOf(BaseDataRow::class)) {
                        $this->_data[$property] = $rowValue instanceof $class ? $rowValue : $class::Create($rowValue);
                    }
                    else {
                        $this->_data[$property] = $rowValue instanceof $class ? $rowValue : new $class($rowValue, $this->Storage(), $field, $this);
                    }
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
    protected function _typeToData(mixed $data): mixed
    {

        $storage = $this->Storage();
        foreach ($data as $key => $value) {

            $fieldName = $storage->GetFieldName($key);

            /** @var Field */
            $field = $storage->fields->$fieldName ?? null;
            if ($field) {

                // подбираем значение по умолчанию
                if (is_null($value) && !is_null($field->default)) {
                    if($field->type === 'json' && strstr($field->default, 'json_array') !== false) {
                        $value = '[]';
                    } elseif ($field->type === 'json' && strstr($field->default, 'json_object') !== false) {
                        $value = '{}';
                    } else {
                        $value = $field->default;
                    }
                }

                if ($field->class === 'string') {
                    $data[$key] = str_replace("\r\n", "\n", (string) $value);
                } elseif ($field->class == 'bool') {
                    if(in_array($value, [true, '1', 1, 'true'])) {
                        $data[$key] = true;
                    } elseif (in_array($value, [false, '0', 0, 'false'])) {
                        $data[$key] = false;
                    } else {
                        $data[$key] = null;
                    }
                } elseif ($field->class == 'int') {
                    $data[$key] = !is_numeric($value) ? null : (int)$value;
                } elseif ($field->class == 'float') {
                    $data[$key] = !is_numeric($value) ? null : (float)$value;
                } elseif ($field->class == 'uuid') {                    
                    $data[$key] = $value instanceof UUIDField ? $value->binary : $value;
                } elseif ($field->class === 'array') {
                    $data[$key] = is_string($value) ? $value : json_encode($value);
                } else {
                    $data[$key] = is_null($value) ? null : (string) $value;
                }

                if($field->required && is_null($value)) {
                    throw new ValidationException('The ' . $key . ' field is required for storage ' . $storage->name, 500, null);
                }

            }
        }

        return $data;
    }

    protected function _processDefaultValues(): bool
    {
        foreach($this->_storage->fields as $fieldName => $field) {
            $realFieldName = $this->_storage->GetRealFieldName($fieldName);
            if(!is_null($field->default) && !isset($this->_data[$realFieldName])) {
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
        foreach($fields as $fieldName => $fieldData) {
            /** @var Field $fieldData */
            $fieldValue = $this->$fieldName;
            if(is_null($fieldValue)) {
                $return[$fieldName] = null;
                continue;
            }
            if ($fieldData->isLookup) {
                if(is_array($fieldValue)) {
                    $ret = [];
                    foreach($fieldValue as $value) {
                        if (is_object($value) && method_exists($value, 'GetValidationData')) {
                            $ret[] = $value->GetValidationData();
                        }
                        else {
                            $ret[] = $value->{$fieldData->lookup->GetValueField()};
                        }
                    }
                    $return[$fieldName] = $ret;
                }
                else {
                    if (is_object($fieldValue) && method_exists($fieldValue, 'GetValidationData')) {
                        $return[$fieldName] = $fieldValue->GetValidationData();
                    }
                    else {
                        $return[$fieldName] = $fieldValue->{$fieldData->lookup->GetValueField()};
                    }
                }
            } elseif ($fieldData->class === 'string') {
                $return[$fieldName] = (string) $fieldValue;
            } elseif ($fieldData->class === 'int') {
                $return[$fieldName] = (int) $fieldValue;
            } elseif ($fieldData->class === 'float') {
                $return[$fieldName] = (float) $fieldValue;
            } elseif ($fieldData->class === 'bool') {
                $return[$fieldName] = (bool) $fieldValue;
            } elseif ($fieldData->class === 'array') {
                $return[$fieldName] = (array) $fieldValue;
            } elseif (strstr($fieldData->class, 'ValueField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif (strstr($fieldData->class, 'UUIDField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif (strstr($fieldData->class, 'DateField') !== false || strstr($fieldData->class, 'DateTimeField') !== false) {
                $return[$fieldName] = (string) $fieldValue;
            } elseif (method_exists($fieldValue, 'GetValidationData')) {
                $return[$fieldName] = $fieldValue->GetValidationData();
            }
        }

        return (object)$return;
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

        $originalValue = ($original->$property ?? null);
        $newValue = ($data[$property] ?? null);

        if(is_string($newValue) && StringHelper::IsJsonString($newValue)) {
            $newValue = json_decode($newValue);
            $originalValue = json_decode($originalValue);
        }

        return $originalValue != $newValue;
    }

    /**
     * Вызывает SaveRow у таблицы
     * @return QueryInfo|bool 
     */
    public function Save(bool $performValidationBeforeSave = false): QueryInfo|bool
    {
        if($performValidationBeforeSave) {
            $this->Validate(true);
        }
        $return = $this->table->SaveRow($this);
        $this->_processDefaultValues();
        return $return;
    }

    public function Delete(): QueryInfo
    {
        return $this->table->DeleteRow($this);
    }

}
