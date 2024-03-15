<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Models
 */
namespace Colibri\Data\Models;

use Colibri\Utils\ExtendedObject;
use Colibri\Common\Encoding;

/**
 * Represents a DataRow in a data table.
 *
 * The DataRow class extends the ExtendedObject class and provides functionality
 * for working with individual rows of data within a data table. It typically
 * corresponds to a single record or entry in a database table.
 *
 * @property array $properties
 * @property bool $changed
 * @property-read DataTable $table
 * @property int $id
 */
class DataRow extends ExtendedObject
{

    /**
     * Data Table object
     *
     * @var DataTable
     */
    protected ? DataTable $_table = null;

    /**
     * Constructor
     *
     * @param DataTable $table
     * @param mixed $data
     * @param string $tablePrefix
     */
    public function __construct(DataTable $table, mixed $data, string $tablePrefix = '')
    {
        parent::__construct($data, $tablePrefix);
        $this->_table = $table;
    }

    /**
     * Magic method to handle property access via the __get() syntax.
     *
     * This method is automatically called when an inaccessible or non-existent property
     * is accessed using the __get() syntax (e.g., $object->propertyName). It allows you
     * to customize the behavior of property access within your class.
     *
     * @param string $property The name of the property being accessed.
     *
     * @return mixed The value of the accessed property (or any custom logic you define).
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
     * Magic method to handle property assignment via the __set() syntax.
     *
     * This method is automatically called when an inaccessible or non-existent property
     * is set using the __set() syntax (e.g., $object->propertyName = $value). It allows
     * you to customize the behavior of property assignment within your class.
     *
     * @param string $property The name of the property being assigned.
     * @param mixed $value The value to assign to the property.
     *
     * @return void
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
     * Copies data from the current object to an ExtendedObject.
     *
     * This method copies relevant data from the current instance to a new instance
     * of the ExtendedObject class. It allows you to create a similar object with
     * additional functionality provided by ExtendedObject.
     *
     * @return ExtendedObject A new instance of ExtendedObject with copied data.
     */
    public function CopyToObject(): ExtendedObject
    {
        return new ExtendedObject($this->_data, $this->_prefix);
    }

    /**
     * Retrieves the type of a specific property.
     *
     * This method returns the data type associated with the given property name.
     * It allows you to determine the expected type of a property within the class.
     *
     * @param string $property The name of the property.
     *
     * @return string The data type (e.g., 'string', 'int', 'bool', etc.) of the property.
     */
    public function Type(string $property): string
    {
        $fields = $this->properties;
        foreach ($fields as $prop => $field) {
            if ($prop == $property) {
                return $field->type;
            }
        }
        return 'varchar';
    }

    /**
     * Checks if a specific property has been changed.
     *
     * @param string $property The name of the property to check for changes.
     * @param bool $convertData (optional) Whether to convert data before comparison. Default is false.
     * @return bool True if the property has been changed, false otherwise.
     */
    public function IsPropertyChanged(string $property, bool $convertData = false): bool
    {
        if ($this->Type($property) === 'JSON') {

            $originalValue = $this->Original()?->$property;
            $originalValue = !$convertData ? Encoding::Convert($originalValue, Encoding::UTF8) : $originalValue;
            $originalValue = json_decode($originalValue);

            $dataValue = $this->_data[$property];
            $dataValue = !$convertData ? Encoding::Convert($dataValue, Encoding::UTF8) : $dataValue;
            $dataValue = json_decode($dataValue);

            return $originalValue != $dataValue;

        } else {
            return parent::IsPropertyChanged($property);
        }
    }


}