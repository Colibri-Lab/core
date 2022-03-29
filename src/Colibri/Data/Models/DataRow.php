<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Models
 */

namespace Colibri\Data\Models;

use Colibri\Utils\ExtendedObject;
use Colibri\Common\Encoding;

/**
 * Представление строки данных
 *
 * @property array $properties
 * @property bool $changed
 * @property-read DataTable $table
 *
 * @testFunction testDataRow
 */
class DataRow extends ExtendedObject
{

    /**
     * Таблица
     *
     * @var DataTable
     */
    protected ?DataTable $_table = null;

    /**
     * Конструктор
     *
     * @param DataTable $table
     * @param mixed $data
     * @param string $tablePrefix
     */
    public function __construct(DataTable $table,  mixed $data, string $tablePrefix = '')
    {
        parent::__construct($data, $tablePrefix);
        $this->_table = $table;
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     * @testFunction testDataRow__get
     */
    public function __get(string $property): mixed
    {
        $return = null;
        $property = strtolower($property);
        if ($property == 'properties') {
            $return = $this->_table->Fields();
        } elseif ($property == 'changed') {
            $return = $this->_changed;
        } elseif ($property == 'table') {
            $return = $this->_table;
        } else {
            $return = parent::__get($property);
        }
        return $return;
    }

    /**
     * Сеттер
     *
     * @param string $property
     * @param mixed $value
     * @testFunction testDataRow__set
     */
    public function __set(string $property, mixed $value): void
    {
        $property = strtolower($property);
        if ($property == 'properties') {
            throw new DataModelException('Can not set the readonly property');
        } elseif ($property == 'changed') {
            $this->_changed = $value;
        } else {
            parent::__set($property, $value);
        }
    }

    /**
     * Копирует в обьект
     *
     * @return ExtendedObject
     * @testFunction testDataRowCopyToObject
     */
    public function CopyToObject(): ExtendedObject
    {
        return new ExtendedObject($this->_data, $this->_prefix);
    }

    public function Type(string $property): string 
    {
        $fields = $this->properties;
        foreach ($fields as $prop => $field) {
            if($prop == $property) {
                return $field->type;
            }
        }
        return 'varchar';
    }

    public function IsPropertyChanged(string $property, bool $convertData = false): bool
    {
        if($this->Type($property) === 'JSON') {
            
            $originalValue = $this->Original()?->$property;
            $originalValue = !$convertData ? Encoding::Convert($originalValue, Encoding::UTF8) : $originalValue;
            $originalValue = json_decode($originalValue);

            $dataValue = $this->_data[$property];
            $dataValue = !$convertData ? Encoding::Convert($dataValue, Encoding::UTF8) : $dataValue;
            $dataValue = json_decode($dataValue);

            return $originalValue != $dataValue;

        }
        else {
            return parent::IsPropertyChanged($property); 
        }
    }


}
