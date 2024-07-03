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
 * Модель поля хранлища
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 *
 * @property-read array $raw данные поля
 * @property-read object $fields поля внутри поля
 * @property-read bool $isLookup да, если поле связано с другой таблицей
 * @property-read bool $isValues да, если есть значения
 * @property-read Lookup $lookup связка
 * @property object<string, string> $values значения
 * @property-read mixed $default значение по умолчанию
 * @property-read bool $required да, если поле обязательное
 * @property-read bool $readonly да, если поле запрещено к редактированию
 * @property-read bool $inTemplate да, если поле должно отображаться в шаблоне
 * @property-read Field $parent
 * @property-read array $path
 * @property-read ?string $param тип поля в запросе
 * @property string $formula формула
 * @property array $rawvalues
 *
 */
class Field
{
    /**
     * Хранилище
     * @var Storage
     */
    private ?Storage $_storage = null;

    /**
     * Список поле внутри текущего поля
     * @var object
     */
    private $_fields;

    /**
     * Данные поля
     * @var array
     */
    private $_xfield;

    /**
     * Данные связки
     * @var Lookup
     */
    private $_lookup;

    /**
     * Список возможных значени
     * @var string[string]
     */
    private $_values;

    /**
     * Формула
     * @var string
     */
    private $_formula;

    private ?Field $_parent = null;

    /**
     * Конструктор
     * @param array $xfield данные поле
     * @param Storage $storage хранилище
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

    private function _init()
    {
        $this->_loadValues();
        $this->_loadFields();
        $this->_loadFormula();
    }

    /**
     * Загружает формулу
     * @return void
     */
    private function _loadFormula(): void
    {
        $this->_formula = isset($this->_xfield['formula']) ? $this->_xfield['formula'] : null;
    }

    /**
     * Загружает значения
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
     * Загружает поля
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
     * Геттер
     * @param string $prop свойство
     * @return mixed значение
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
                return $this->_xfield['params']['required'] ?? $this->_xfield['required'] ?? null;
            case 'islookup':
                return $this->_lookup && ($this->_lookup->accessPoint !== null || $this->_lookup->storage !== null);
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
                if(in_array($this->_xfield['type'], [
                    'varchar',
                    'char',
                    'text',
                    'mediumtext',
                    'longtext',
                    'date',
                    'datetime'
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

    public function __set(string $prop, mixed $value): void
    {
        if (isset($this->_xfield[$prop])) {
            $this->_xfield[$prop] = $value;
            $this->_init();
        }
    }

    public function ToArray(): array
    {
        return $this->_xfield;
    }

    public function Path(): array {
        $path = [];
        $parent = $this;
        while($parent) {
            $path[] = $parent->{'name'};
            $parent = $this->parent;
        }
        return array_reverse($path);
    }

    public function UpdateField(Field $field)
    {
        $this->_xfield['fields'][$field->{'name'}] = $field->ToArray();
        if ($this->_parent) {
            $this->_parent->UpdateField($this);
        } else {
            $this->_storage->UpdateField($this);
        }
    }

    public function Save()
    {
        $xfield = $this->ToArray();
        unset($xfield['name']);
        foreach ($this->_fields as $fname => $field) {
            $xfield['fields'][$fname] = $field->Save();
        }
        return $xfield;
    }

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

    public function UpdateData($data): void
    {
        foreach ($data as $key => $value) {
            if (
                ($key == 'lookup' && array_key_exists('none', $value)) ||
                ($key == 'values' && empty($value)) ||
                ($key == 'selector' && (!isset($value['ondemand']) || $value['ondemand'] === false) && (!isset($value['value']) || $value['value'] === '') && (!isset($value['title']) || $value['title'] === '') && (!isset($value['__render']) || $value['__render'] === '')) ||
                ($key == 'note' && empty($value)) ||
                ($key == 'desc' && empty($value))
            ) {
                if (isset($this->_xfield[$key])) {
                    unset($this->_xfield[$key]);
                }
            } elseif ($key !== 'fields') {
                if ($key === 'selector') {
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
                $this->_xfield[$key] = $value;
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
