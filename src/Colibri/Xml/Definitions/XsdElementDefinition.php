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

use Colibri\Xml\Serialization\XmlSerialized;
use Colibri\Xml\XmlNode;

/**
 * Определение элемента
 * 
 * @property-read string $annotation аннотация элемента
 * @property-read string $name наименование элемента
 * @property-read \stdClass $occurs обьект определяющий с какого по какое количество может быть вхождений данного элемента
 * @property-read XsdAttributeDefinition[] $attributes список атрибутов
 * @property-read XsdElementDefinition[] $elements список элементов
 * @property-read XmlNode $type тип элемента
 * @property-read array $autocomplete список значений для интеллисенса
 * @property-read string $generate команда для генерации элемента
 * @property-read string $lookup команда для генерации межобьектных связей
 * @testFunction testXsdElementDefinition
 */
class XsdElementDefinition implements \JsonSerializable
{

    /**
     * Узел
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
     * @param XmlNode $elementNode описываемый элемент
     * @param mixed $schema схема
     */
    public function __construct(XmlNode $elementNode, mixed $schema)
    {
        $this->_node = $elementNode;
        $this->_schema = $schema;
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     * @testFunction testXsdElementDefinition__get
     */
    public function __get(string $property): mixed
    {
        if (strtolower($property) == 'annotation') {
            return $this->_node->Item('xs:annotation') ? trim($this->_node->Item('xs:annotation')->value, "\r\t\n ") : '';
        } elseif (strtolower($property) == 'name') {
            return $this->_node->attributes->{'name'}->value;
        } elseif (strtolower($property) == 'occurs') {
            return (object) ['min' => ($this->_node->attributes->{'minOccurs'} ? $this->_node->attributes->{'minOccurs'}->value : 'unbounded'), 'max' => ($this->_node->attributes->{'maxOccurs'} ? $this->_node->attributes->{'maxOccurs'}->value : 'unbounded')];
        } elseif (strtolower($property) == 'attributes') {
            $attributes = [];
            $type = $this->_node->Item('xs:complexType');
            if ($type) {
                foreach ($type->Query('./xs:attribute') as $attr) {
                    $a = new XsdAttributeDefinition($attr, $this->_schema);
                    $attributes[$a->name] = $a;
                }
            }
            return $attributes;
        } elseif (strtolower($property) == 'elements') {
            $type = $this->_node->Item('xs:complexType');
            if (!$type) {
                return [];
            }
            $sequence = $type->Item('xs:sequence');
            if ($sequence) {
                $elements = [];
                foreach ($sequence->Query('./xs:element') as $element) {
                    $el = new XsdElementDefinition($element, $this->_schema);
                    $elements[$el->name] = $el;
                }
                return $elements;
            } else {
                return [];
            }
        } elseif (strtolower($property) == 'type') {
            if ($this->_node->attributes->{'type'}) {
                return isset($this->_schema->types[$this->_node->attributes->{'type'}->value]) ? $this->_schema->types[$this->_node->attributes->{'type'}->value] : new XsdBaseTypeDefinition($this->_node->attributes->{'type'}->value);
            }
            $type = $this->_node->Item('xs:simpleType');
            if (!$type) {
                return null;
            }
            return new XsdBaseTypeDefinition($type);
        } elseif (strtolower($property) == 'autocomplete') {
            return $this->_node->attributes->{'autocomplete'} && $this->_node->attributes->{'autocomplete'}->value ? explode(',', $this->_node->attributes->{'autocomplete'}->value) : null;
        } elseif (strtolower($property) == 'generate') {
            return $this->_node->attributes->{'generate'} && $this->_node->attributes->{'generate'}->value ? $this->_node->attributes->{'generate'}->value : null;
        } elseif (strtolower($property) == 'lookup') {
            return $this->_node->attributes->{'lookup'} && $this->_node->attributes->{'lookup'}->value ? $this->_node->attributes->{'lookup'}->value : null;
        }
        return null;
    }

    /**
     * Создает обьект XmlSerialized по определению
     *
     * @return XmlSerialized
     * @testFunction testXsdElementDefinitionCreateObject
     */
    public function CreateObject(): XmlSerialized
    {

        $attributes = [];
        foreach ($this->attributes as $attr) {
            $attributes[$attr->name] = $attr->default ? $attr->default : null;
        }

        if ($this->type && count($this->type->attributes)) {
            foreach ($this->type->attributes as $attr) {
                $attributes[$attr->name] = $attr->default ? $attr->default : null;
            }
        }

        $content = [];
        foreach ($this->elements as $element) {
            $content[$element->name] = $element->CreateObject();
        }

        return new XmlSerialized($this->name, $attributes, $content);
    }

    /**
     * Возвращает данные в виде простого обьекта для упаковки в json
     *
     * @return object
     * @testFunction testXsdElementDefinitionJsonSerialize
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('name' => $this->name, 'type' => $this->type, 'annotation' => $this->annotation, 'occurs' => $this->occurs, 'attributes' => $this->attributes, 'elements' => $this->elements, 'autocomplete' => $this->autocomplete, 'generate' => $this->generate, 'lookup' => $this->lookup);
    }

    /**
     * Возвращает данные в виде простого обьекта
     *
     * @return object
     * @testFunction testXsdElementDefinitionToObject
     */
    public function ToObject(): object
    {

        $attributes = [];
        foreach ($this->attributes as $attr) {
            $attributes[] = $attr->ToObject();
        }

        $elements = [];
        foreach ($this->elements as $element) {
            $elements[$element->name] = $element->ToObject();
        }

        return (object) array('name' => $this->name, 'type' => ($this->type ? $this->type->ToObject() : null), 'annotation' => $this->annotation, 'occurs' => $this->occurs, 'attributes' => $attributes, 'elements' => $elements, 'autocomplete' => $this->autocomplete, 'generate' => $this->generate, 'lookup' => $this->lookup);
    }
}