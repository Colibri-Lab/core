<?php

/**
 * Definitions
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Definitions
 *
 */

namespace Colibri\Xml\Definitions;

use Colibri\Xml\XmlNode;

/**
 * Схема
 * 
 * @property-read XsdSimpleTypeDefinition[] $types типы в схеме
 * @property-read XsdElementDefinition[] $elements элементы в схеме
 * @testFunction testXsdSchemaDefinition
 */
class XsdSchemaDefinition implements \JsonSerializable
{

    /**
     * Схема
     *
     * @var XmlNode
     */
    private ? XmlNode $_schema;

    /**
     * Массив типов
     *
     * @var array
     */
    private array $_types;

    /**
     * Конструктор
     *
     * @param string $fileName название файла
     * @param boolean $isFile файл или не файл
     */
    public function __construct(string $fileName, bool $isFile = true)
    {
        $this->_schema = XmlNode::Load($fileName, $isFile);
        $this->_loadComplexTypes();
    }

    /**
     * Загружает схему из файла или строки
     *
     * @param string $fileName название файла
     * @param boolean $isFile файл или не файл
     * @return void
     */
    /**
     * @testFunction testXsdSchemaDefinitionLoad
     */
    public static function Load(string $fileName, bool $isFile = true): XsdSchemaDefinition
    {
        return new XsdSchemaDefinition($fileName, $isFile);
    }

    /**
     * Загружает все типы в список
     *
     * @return void
     * @testFunction testXsdSchemaDefinition_loadComplexTypes
     */
    private function _loadComplexTypes(): void
    {
        $this->_types = [];
        $types = $this->_schema->Query('//xs:simpleType[@name]');
        foreach ($types as $type) {
            $t = new XsdSimpleTypeDefinition($type);
            $this->_types[$t->name] = $t;
        }

        $types = $this->_schema->Query('//xs:complexType[@name]');
        foreach ($types as $type) {
            $t = new XsdSimpleTypeDefinition($type);
            $this->_types[$t->name] = $t;
        }
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     * @testFunction testXsdSchemaDefinition__get
     */
    public function __get(string $property): mixed
    {
        if (strtolower($property) == 'types') {
            return $this->_types;
        } elseif (strtolower($property) == 'elements') {
            $elements = [];
            foreach ($this->_schema->Query('./xs:element') as $element) {
                $el = new XsdElementDefinition($element, $this);
                $elements[$el->name] = $el;
            }
            return $elements;
        }
        return null;
    }

    /**
     * Возвращает данные в виде простого обьекта для упаковки в json
     *
     * @return object
     * @testFunction testXsdSchemaDefinitionJsonSerialize
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('types' => $this->types, 'elements' => $this->elements);
    }

    /**
     * Возвращает данные в виде простого обьекта
     *
     * @return object
     * @testFunction testXsdSchemaDefinitionToObject
     */
    public function ToObject(): object
    {

        $types = [];
        foreach ($this->types as $type) {
            $types[$type->name] = $type->ToObject();
        }

        $elements = [];
        foreach ($this->elements as $element) {
            $elements[$element->name] = $element->ToObject();
        }

        return (object) array('types' => $types, 'elements' => $elements);
    }
}