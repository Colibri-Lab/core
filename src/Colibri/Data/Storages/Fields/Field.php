<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */

namespace Colibri\Data\Storages\Fields;

use Colibri\Data\Storages\Storage;
use Colibri\Data\Storages\Fields\Lookup;

/**
 * Storage model
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 *
 * @property-read array $raw raw data
 * @property-read object $fields fields inside the field
 * @property-read bool $isLookup true if the field is linked to another table
 * @property-read bool $isValues true if there are values
 * @property-read Lookup $lookup lookup
 * @property object<string, string> $values values
 * @property-read mixed $default default value
 * @property-read bool $required true if the field is required
 * @property-read bool $readonly true if the field is read-only
 * @property-read bool $inTemplate true if the field should be displayed in the template
 * @property-read Field $parent parent field
 * @property-read array $path path to the field
 * @property-read ?string $param parameter type of the field in the query
 * @property string $formula formula
 * @property array $rawvalues raw values
 *
 */
class Field
{
    /**
     * Storage
     * @var Storage
     */
    private ?Storage $_storage = null;

    /**
     * List of fields inside the current field
     * @var object
     */
    private $_fields;

    /**
     * Field data
     * @var array
     */
    private $_xfield;

    /**
     * Lookup data
     * @var Lookup
     */
    private $_lookup;

    /**
     * List of possible values
     * @var string[string]
     */
    private $_values;

    /**
     * Formula
     * @var string
     */
    private $_formula;

    /**
     * Parent field
     * @var Field
     */
    private ?Field $_parent = null;

    /**
     * Constructor
     * @param array $xfield field data
     * @param Storage $storage storage
     * @return void
     */
    public function __construct(array $xfield, ?Storage $storage = null, ?Field $parent = null)
    {
        $this->_storage = $storage;
        $this->_xfield = $xfield;
        $this->_parent = $parent;

        $this->_lookup = new Lookup($xfield, $storage);
        $this->_init();
    }

    /**
     * Initializes the field by loading values, fields, and formula.
     * @return void
     */
    private function _init()
    {
        $this->_loadValues();
        $this->_loadFields();
        $this->_loadFormula();
    }

    /**
     * Loads the formula
     * @return void
     */
    private function _loadFormula(): void
    {
        $this->_formula = isset($this->_xfield['formula']) ? $this->_xfield['formula'] : null;
    }

    /**
     * Loads the values
     * @return void
     */
    private function _loadValues(): void
    {
        $this->_values = [];
        if (!isset($this->_xfield['values'])) {
            return;
        }

        $values = $this->_xfield['values'];
        foreach ($values as $value) {
            if (!is_array($value)) {
                $value = ['value' => $value, 'title' => $value];
            }
            if (isset($value['type']) && $value['type'] === 'number') {
                $value['value'] = (float) $value['value'];
            } elseif (isset($value['type']) && $value['type'] === 'text') {
                $value['value'] = (string) $value['value'];
            }
            $this->_values[$value['value']] = isset($value['title']) ? $value['title'] : $value['value'];
        }
    }

    /**
     * Loads the fields
     * @return void
     */
    private function _loadFields(): void
    {
        $this->_fields = (object) [];
        if (!isset($this->_xfield['fields'])) {
            return;
        }

        $xfields = $this->_xfield['fields'];
        foreach ($xfields as $name => $xfield) {
            $xfield['name'] = $name;
            $this->_fields->$name = new Field($xfield, $this->_storage, $this);
        }
    }

    /**
     * Getter
     * @param string $prop property
     * @return mixed value
     */
    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        switch ($prop) {
            case 'raw':
                return $this->_xfield;
            case 'fields':
                return $this->_fields;
            case 'lookup':
                return $this->_lookup;
            case 'values':
                return $this->_values;
            case 'storage':
                return $this->_storage;
            case 'formula':
                return $this->_formula;
            case 'required':
                return $this->_xfield['params']['required'] ?? $this->_xfield['required'] ?? false;
            case 'islookup':
                return $this->_lookup && ($this->_lookup->accesspoint !== null || $this->_lookup->storage !== null);
            case 'isvalues':
                return count((array) $this->_values) > 0;
            case 'hasdefault':
                return isset($this->_xfield['default']) && $this->_xfield['default'] !== null;
            case 'parent':
                return $this->_parent;
            case 'path':
                return $this->Path();
            case 'rawvalues':
                return isset($this->_xfield['values']) ? $this->_xfield['values'] : null;
            case 'param':
                $storage = $this->_storage;
                $datapoint = $storage->accessPoint;
                $types = $datapoint->allowedTypes;
                if(isset($types[$this->_xfield['type']])) {
                    return $types[$this->_xfield['type']]['param'];
                }

                if(in_array($this->_xfield['type'], [
                    'varchar',
                    'char',
                    'text',
                    'mediumtext',
                    'longtext',
                    'date',
                    'datetime',
                    'timestamp',
                ])) {
                    return 'string';
                } elseif (in_array($this->_xfield['type'], ['int', 'float', 'bigint', 'double','bool','tinyint'])) {
                    return 'integer';
                } elseif ($this->_xfield['type'] === 'enum') {
                    return $this->_xfield['values'][0]['type'] === 'text' ? 'string' : 'integer';
                } else {
                    return null;
                }
                // no break
            default:
                return isset($this->_xfield[$prop]) ? $this->_xfield[$prop] : null;
        }
    }

    /**
     * Setter
     * @param string $prop property
     * @param mixed $value value
     * @return void
     */
    public function __set(string $prop, mixed $value): void
    {
        if (isset($this->_xfield[$prop])) {
            $this->_xfield[$prop] = $value;
            $this->_init();
        }
    }

    /**
     * Converts the field to an array representation.
     * @return array The array representation of the field.
     */
    public function ToArray(): array
    {
        return $this->_xfield;
    }

    /**
     * Returns the path to the field as an array of field names.
     * @return array An array representing the path to the field.
     */
    public function Path(): array
    {
        $path = [];
        $parent = $this;
        while($parent) {
            $path[] = $parent->{'name'};
            $parent = $this->parent;
        }
        return array_reverse($path);
    }

    /**
     * Updates the field with new data and propagates the update to the parent or storage.
     * @param Field $field The field to update.
     * @return void
     */
    public function UpdateField(Field $field)
    {
        $this->_xfield['fields'][$field->{'name'}] = $field->ToArray();
        if ($this->_parent) {
            $this->_parent->UpdateField($this);
        } else {
            $this->_storage->UpdateField($this);
        }
    }

    /**
     * Saves the field and its subfields.
     * @return array The saved field data.
     */
    public function Save(): array
    {
        $xfield = $this->ToArray();
        unset($xfield['name']);
        foreach ($this->_fields as $fname => $field) {
            $xfield['fields'][$fname] = $field->Save();
        }
        return $xfield;
    }

    /**
     * Returns the default value of the field.
     * @return mixed The default value of the field.
     */
    public function AddField($name, $data): Field
    {
        if (!isset($this->_xfield['fields'])) {
            $this->_xfield['fields'] = [];
        }

        $data['name'] = $name;
        $this->_xfield['fields'][$name] = $data;
        $this->_fields->$name = new Field($this->_xfield['fields'][$name], $this->_storage, $this);
        $this->UpdateField($this->_fields->$name);
        return $this->_fields->$name;

    }

    /**
     * Returns the default value of the field.
     * @return mixed The default value of the field.
     */
    private function _isEmptyLang($value): bool
    {
        if(is_string($value)) {
            return empty($value);
        }
        $isEmpty = true;
        foreach($value as $lang => $v) {
            if(!empty($v)) {
                $isEmpty = false;
                break;
            }
        }
        return $isEmpty;
    }

    /**
     * Updates the field data with the provided data and propagates the update to the parent or storage.
     * @param array $data The data to update the field with.
     * @return void
     */
    public function UpdateData($data): void
    {
        foreach ($data as $key => $value) {
            if (
                ($key == 'lookup' && array_key_exists('none', $value)) ||
                ($key == 'values' && empty($value)) ||
                ($key == 'selector' && (!isset($value['ondemand']) || $value['ondemand'] === false) && (!isset($value['value']) || $value['value'] === '') && (!isset($value['title']) || $value['title'] === '') && (!isset($value['__render']) || $value['__render'] === '')) ||
                ($key == 'placeholder' && (empty($value) || $this->_isEmptyLang($value))) ||
                ($key == 'group' && (empty($value) || $this->_isEmptyLang($value))) ||
                ($key == 'note' && (empty($value) || $this->_isEmptyLang($value))) ||
                ($key == 'desc' && (empty($value) || $this->_isEmptyLang($value)))
            ) {
                if (isset($this->_xfield[$key])) {
                    unset($this->_xfield[$key]);
                }
            } elseif ($key !== 'fields') {
                if ($key === 'params') {
                    foreach($value as $k => $v) {
                        if($k === 'addlink' && $this->_isEmptyLang($v)) {
                            unset($value[$k]);
                        } elseif (in_array($k, ['valuegenerator', 'onchangehandler','size','allow', 'title','displayed_columns','maxadd','generator','transformer','noteClass','simplearraywidth','simplearrayheight','fieldgenerator','mask','viewer','greed'])) {
                            if(empty($v)) {
                                unset($value[$k]);
                            }
                        } elseif ($k === 'validate') {
                            foreach($v as $index => $validator) {
                                if(empty($validator['message']) || $this->_isEmptyLang($validator['message'])) {
                                    unset($value['validate'][$index]);
                                }
                            } 
                            if(empty($value['validate'])) {
                                unset($value['validate']);
                            }
                        }
                    }
                } elseif ($key === 'selector') {
                    if (!$value['title']) {
                        unset($value['title']);
                    }
                    if (!$value['value']) {
                        unset($value['value']);
                    }
                    if (!$value['__render']) {
                        unset($value['__render']);
                    }
                    if (!$value['ondemand']) {
                        unset($value['ondemand']);
                    }
                    if (!$value['emptyvalue']) {
                        unset($value['emptyvalue']);
                    }
                    if (!$value['group']) {
                        unset($value['group']);
                    }
                    if (!$value['chooser']) {
                        unset($value['chooser']);
                    }
                    if ($this->_isEmptyLang($value['emptytitle'])) {
                        unset($value['emptytitle']);
                    }
                } elseif ($key === 'attrs') {
                    if (!isset($value['width']) || !$value['width']) {
                        unset($value['width']);
                    }
                    if (!isset($value['height']) || !$value['height']) {
                        unset($value['height']);
                    }
                    if (!isset($value['class']) || !$value['class']) {
                        unset($value['class']);
                    }
                }
                if(!empty($value)) {
                    $this->_xfield[$key] = $value;
                }
            }

        }

        if (!isset($this->_xfield['hasdefault']) || $this->_xfield['hasdefault'] !== true) {
            unset($this->_xfield['default']);
        }
        unset($this->_xfield['hasdefault']);

        if (isset($this->_xfield['length']) && $this->_xfield['length'] === '') {
            unset($this->_xfield['length']);
        }

        if (isset($this->_xfield['storage'])) {
            unset($this->_xfield['storage']);
        }
        if (isset($this->_xfield['field'])) {
            unset($this->_xfield['field']);
        }


        $this->_loadFields();
        if ($this->_parent) {
            $this->_parent->UpdateField($this);
        } else {
            $this->_storage->UpdateField($this);
        }
    }

    /**
     * Deletes the field and propagates the deletion to the parent or storage.
     * @param string $name The name of the field to delete.
     * @return void
     */
    public function DeleteField($name): void
    {
        unset($this->_xfield['fields'][$name]);
        unset($this->_fields->$name);
        if ($this->_parent) {
            $this->_parent->UpdateField($this);
        } else {
            $this->_storage->UpdateField($this);
        }
    }

    /**
     * Moves a field relative to another field within the same parent.
     * @param Field $field The field to move.
     * @param Field $relative The reference field for positioning.
     * @param string $sibling 'before' or 'after' indicating the position relative to the reference field.
     * @return void
     */
    public function MoveField($field, $relative, $sibling)
    {

        // перемещает во внутреннем массиве
        $xfields = $this->_xfield['fields'];
        if (!isset($xfields[$field->name])) {
            return false;
        }

        $newxFields = [];
        $xfieldMove = $xfields[$field->name];
        foreach ($xfields as $name => $xfield) {
            if ($name != $field->name) {

                if ($name == $relative->name && $sibling === 'before') {
                    $newxFields[$field->name] = $xfieldMove;
                }
                $newxFields[$name] = $xfield;
                if ($name == $relative->name && $sibling === 'after') {
                    $newxFields[$field->name] = $xfieldMove;
                }

            }
        }

        $this->_xfield['fields'] = $newxFields;
        $this->_init();

    }

}
