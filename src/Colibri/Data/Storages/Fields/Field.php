<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */
namespace Colibri\Data\Storages\Fields;

use Colibri\Data\Exception;
use Colibri\Xml\XmlNode;
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
 * @property string[string] $values значения
 * @property-read mixed $default значение по умолчанию
 * @property-read bool $required да, если поле обязательное
 * @property-read bool $readonly да, если поле запрещено к редактированию
 * @property-read bool $inTemplate да, если поле должно отображаться в шаблоне
 * @property string $formula формула
 *
 */
class Field
{
    /**
     * Хранилище
     * @var Storage
     */
    private $_storage;

    /**
     * Список поле внутри текущего поля
     * @var object
     */
    private $_fields;

    /**
     * Данные поля
     * @var XmlNode
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
    
    /**
     * Конструктор
     * @param array $xfield данные поле
     * @param Storage $storage хранилище
     * @return void
     */
    public function __construct($xfield, $storage)
    {
        $this->_storage = $storage;
        $this->_xfield = $xfield;
        
        $this->_lookup = new Lookup($xfield, $storage);
        $this->_loadValues();
        $this->_loadFields();
        $this->_loadFormula();
    }

    /**
     * Загружает формулу
     * @return void
     */
    private function _loadFormula()
    {
        $this->_formula = isset($this->_xfield['formula']) ? $this->_xfield['formula'] : null;
    }
    
    /**
     * Загружает значения
     * @return void
     */
    private function _loadValues()
    {
        $this->_values = array();
        if (!isset($this->_xfield['values'])) {
            return;
        }
        
        $values = $this->_xfield['values'];
        foreach ($values as $value) {
            $this->_values[$value['value']] = $value['title'];
        }
    }
    
    /**
     * Загружает поля
     * @return void
     */
    private function _loadFields()
    {
        $this->_fields = (object)array();
        if (!isset($this->_xfield['fields'])) {
            return;
        }
        
        $fields = $this->_xfield['fields'];
        foreach ($fields as $name => $field) {
            $xfield['name'] = $name;
            $this->_fields->$name = new Field($field, $this->_storage);
        }
    }
    
    /**
     * Геттер
     * @param string $prop свойство
     * @return mixed значение
     */
    public function __get($prop)
    {
        $prop = strtolower($prop);
        switch ($prop) {
            case 'raw': return $this->_xfield;
            case 'fields': return $this->_fields;
            case 'lookup': return $this->_lookup;
            case 'values': return $this->_values;
            case 'storage': return $this->_storage;
            case 'formula': return $this->_formula;
            case 'islookup': return $this->_lookup && ($this->_lookup->accessPoint !== null || $this->_lookup->storage !== null);
            case 'isvalues': return count((array)$this->_values) > 0;
            default: return isset($this->_xfield[$prop]) ? $this->_xfield[$prop] : null;
        }
    }

    public function ToArray() {
        return $this->_xfield;
    }

}
