<?php

/**
 * Definitions
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Definitions
 */

namespace Colibri\Xml\Definitions;

use Colibri\Xml\XmlNode;

/**
 * XsdAttributeDefinition
 *
 * This class represents the definition of an XML attribute.
 *
 * @property-read string $annotation The annotation of the attribute element.
 * @property-read string $name The name of the attribute element.
 * @property-read XsdSimpleTypeDefinition|XsdBaseTypeDefinition $type The type of the attribute element.
 * @property-read string $use The usage of the attribute.
 * @property-read string $default The default value of the attribute.
 * @property-read string $group The group to which the attribute belongs.
 * @property-read array $autocomplete The list of values for autocomplete functionality.
 * @property-read string $generate The command for generating the element.
 * @property-read string $lookup The command for generating inter-object relationships.
 */
class XsdAttributeDefinition implements \JsonSerializable
{

    /**
     * The attribute node.
     *
     * @var XmlNode
     */
    private ? XmlNode $_node;

    /**
     * The schema.
     *
     * @var XsdSchemaDefinition
     */
    private ? XsdSchemaDefinition $_schema;

    /**
     * Constructor.
     *
     * @param XmlNode             $attributeNode The attribute node.
     * @param XsdSchemaDefinition $schema        The schema.
     */
    public function __construct(XmlNode $attributeNode, XsdSchemaDefinition $schema)
    {
        $this->_node = $attributeNode;
        $this->_schema = $schema;
    }

    /**
     * Getter.
     *
     * @param string $name The property name.
     * @return mixed|null The value of the property.
     */
    public function __get(string $name): mixed
    {
        if (strtolower($name) == 'annotation') {
            return $this->_node->Item('xs:annotation') ? trim($this->_node->Item('xs:annotation')->value, "\r\t\n ") : '';
        } elseif (strtolower($name) == 'name') {
            return $this->_node->attributes->{'name'}->value;
        } elseif (strtolower($name) == 'type') {
            if ($this->_node->attributes->{'type'}) {
                return isset($this->_schema->types[$this->_node->attributes->{'type'}->value]) ? $this->_schema->types[$this->_node->attributes->{'type'}->value] : new XsdBaseTypeDefinition($this->_node->attributes->{'type'}->value);
            }
            return new XsdSimpleTypeDefinition($this->_node->Item('xs:simpleType'));
        } elseif (strtolower($name) == 'use') {
            return $this->_node->attributes->{'use'} ? $this->_node->attributes->{'use'}->value : null;
        } elseif (strtolower($name) == 'default') {
            return $this->_node->attributes->{'default'} ? $this->_node->attributes->{'default'}->value : null;
        } elseif (strtolower($name) == 'group') {
            return $this->_node->attributes->{'group'} ? $this->_node->attributes->{'group'}->value : null;
        } elseif (strtolower($name) == 'autocomplete') {
            return $this->_node->attributes->{'autocomplete'} && $this->_node->attributes->{'autocomplete'}->value ? explode(',', $this->_node->attributes->{'autocomplete'}->value) : null;
        } elseif (strtolower($name) == 'generate') {
            return $this->_node->attributes->{'generate'} && $this->_node->attributes->{'generate'}->value ? $this->_node->attributes->{'generate'}->value : null;
        } elseif (strtolower($name) == 'lookup') {
            return $this->_node->attributes->{'lookup'} && $this->_node->attributes->{'lookup'}->value ? $this->_node->attributes->{'lookup'}->value : null;
        }
        return null;
    }

    /**
     * Returns the object for JSON serialization.
     *
     * @return object The object representation of XsdAttributeDefinition.
     */
    public function jsonSerialize(): object
    {
        return (object) array('name' => $this->name, 'annotation' => $this->annotation, 'type' => $this->type, 'use' => $this->use, 'default' => $this->default, 'group' => $this->group, 'autocomplete' => $this->autocomplete, 'generate' => $this->generate, 'lookup' => $this->lookup);
    }

    /**
     * Returns the object as a simple object.
     *
     * @return object The simple object representation of XsdAttributeDefinition.
     */
    public function ToObject(): object
    {
        return (object) array('name' => $this->name, 'annotation' => $this->annotation, 'type' => $this->type->ToObject(), 'use' => $this->use, 'default' => $this->default, 'group' => $this->group, 'autocomplete' => $this->autocomplete, 'generate' => $this->generate, 'lookup' => $this->lookup);
    }
}