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
 * XsdSchemaDefinition
 *
 * Represents the schema definition for XML.
 *
 * @property-read XsdSimpleTypeDefinition[] $types The types defined in the schema.
 * @property-read XsdElementDefinition[] $elements The elements defined in the schema.
 */
class XsdSchemaDefinition implements \JsonSerializable
{
    /**
     * The XML schema node.
     *
     * @var XmlNode
     */
    private ?XmlNode $_schema;

    /**
     * The array of types.
     *
     * @var array
     */
    private array $_types;

    /**
     * Constructor.
     *
     * @param string $fileName The name of the file.
     * @param bool $isFile Specifies if the input is a file or not.
     */
    public function __construct(string $fileName, bool $isFile = true)
    {
        $this->_schema = XmlNode::Load($fileName, $isFile);
        $this->_loadComplexTypes();
    }

    /**
     * Loads the schema from a file or string.
     *
     * @param string $fileName The name of the file.
     * @param bool $isFile Specifies if the input is a file or not.
     * @return XsdSchemaDefinition
     */
    public static function Load(string $fileName, bool $isFile = true): XsdSchemaDefinition
    {
        return new XsdSchemaDefinition($fileName, $isFile);
    }

    /**
     * Loads all types into the list.
     *
     * @return void
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
     * Getter.
     *
     * @param string $property The name of the property.
     * @return mixed
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
     * Returns the data as a plain object for JSON serialization.
     *
     * @return object
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('types' => $this->types, 'elements' => $this->elements);
    }

    /**
     * Returns the data as a plain object.
     *
     * @return object
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
