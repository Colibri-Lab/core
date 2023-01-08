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
 * Представление атрибута
 * 
 * @property-read string $annotation аннотация элемента
 * @property-read string $name название элемента
 * @property-read XsdSimpleTypeDefinition|XsdBaseTypeDefinition $type тип элемента
 * @property-read string $use использование
 * @property-read string $default значение по умолчанию
 * @property-read string $group группа
 * @property-read array $autocomplete список значений для интеллисенса
 * @property-read string $generate команда для генерации элемента
 * @property-read string $lookup команда для генерации межобьектных связей
 * @testFunction testXsdAttributeDefinition
 */
class XsdAttributeDefinition implements \JsonSerializable
{

    /**
     * Узел атрибута
     *
     * @var XmlNode
     */
    private ? XmlNode $_node;

    /**
     * Схема
     *
     * @var XsdSchemaDefinition
     */
    private ? XsdSchemaDefinition $_schema;

    /**
     * Конструктор
     *
     * @param XmlNode $attributeNode
     * @param XsdSchemaDefinition $schema
     */
    public function __construct(XmlNode $attributeNode, XsdSchemaDefinition $schema)
    {
        $this->_node = $attributeNode;
        $this->_schema = $schema;
    }

    /**
     * Геттер
     *
     * @param string $name
     * @return mixed
     * @testFunction testXsdAttributeDefinition__get
     */
    public function __get(string $name): mixed
    {
        if (strtolower($name) == 'annotation') {
            return $this->_node->Item('xs:annotation') ? trim($this->_node->Item('xs:annotation')->value, "\r\t\n ") : '';
        } elseif (strtolower($name) == 'name') {
            return $this->_node->attributes->name->value;
        } elseif (strtolower($name) == 'type') {
            if ($this->_node->attributes->type) {
                return isset($this->_schema->types[$this->_node->attributes->type->value]) ? $this->_schema->types[$this->_node->attributes->type->value] : new XsdBaseTypeDefinition($this->_node->attributes->type->value);
            }
            return new XsdSimpleTypeDefinition($this->_node->Item('xs:simpleType'));
        } elseif (strtolower($name) == 'use') {
            return $this->_node->attributes->use ? $this->_node->attributes->use->value : null;
        } elseif (strtolower($name) == 'default') {
            return $this->_node->attributes->default ? $this->_node->attributes->default->value : null;
        } elseif (strtolower($name) == 'group') {
            return $this->_node->attributes->group ? $this->_node->attributes->group->value : null;
        } elseif (strtolower($name) == 'autocomplete') {
            return $this->_node->attributes->autocomplete && $this->_node->attributes->autocomplete->value ? explode(',', $this->_node->attributes->autocomplete->value) : null;
        } elseif (strtolower($name) == 'generate') {
            return $this->_node->attributes->generate && $this->_node->attributes->generate->value ? $this->_node->attributes->generate->value : null;
        } elseif (strtolower($name) == 'lookup') {
            return $this->_node->attributes->lookup && $this->_node->attributes->lookup->value ? $this->_node->attributes->lookup->value : null;
        }
    }

    /**
     * Возвращает данные в виде простого обьекта для упаковки в json
     *
     * @return object
     * @testFunction testXsdAttributeDefinitionJsonSerialize
     */
    public function jsonSerialize(): object
    {
        return (object) array('name' => $this->name, 'annotation' => $this->annotation, 'type' => $this->type, 'use' => $this->use, 'default' => $this->default, 'group' => $this->group, 'autocomplete' => $this->autocomplete, 'generate' => $this->generate, 'lookup' => $this->lookup);
    }

    /**
     * Возвращает данные в виде простого обьекта
     *
     * @return object
     * @testFunction testXsdAttributeDefinitionToObject
     */
    public function ToObject(): object
    {
        return (object) array('name' => $this->name, 'annotation' => $this->annotation, 'type' => $this->type->ToObject(), 'use' => $this->use, 'default' => $this->default, 'group' => $this->group, 'autocomplete' => $this->autocomplete, 'generate' => $this->generate, 'lookup' => $this->lookup);
    }
}