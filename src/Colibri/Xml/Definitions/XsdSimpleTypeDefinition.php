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
 * XsdSimpleTypeDefinition
 *
 * Represents a simple data type.
 *
 * @property-read string $name The name of the type.
 * @property-read string $annotation The annotation of the type.
 * @property-read object $restrictions The restrictions of the type.
 * @property-read object $attributes The attributes of the type.
 */
class XsdSimpleTypeDefinition implements \JsonSerializable
{
    /**
     * The type node.
     *
     * @var XmlNode|null
     */
    private ?XmlNode $_node;

    /**
     * The schema.
     *
     * @var XsdSchemaDefinition|null
     */
    private ?XsdSchemaDefinition $_schema;

    /**
     * Constructor.
     *
     * @param XmlNode|null $typeNode The XML node representing the type.
     */
    public function __construct(?XmlNode $typeNode)
    {
        $this->_node = $typeNode;
    }

    /**
     * Getter.
     *
     * @param string $property The name of the property.
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        if (strtolower($property) == 'name') {
            return $this->_node->attributes->{'name'} ? $this->_node->attributes->{'name'}->value : 'simpleType';
        } elseif (strtolower($property) == 'annotation') {
            $annotation = [];
            $anno = $this->_node->Query('./xs:annotation');
            foreach ($anno as $a) {
                $annotation[] = $a->value;
            }
            return trim(implode('', $annotation), "\n\r\t ");
        } elseif (strtolower($property) == 'restrictions') {
            $rest = $this->_node->Item('xs:restriction');
            if (!$rest) {
                return null;
            }
            $returnRestrictions = (object) ['base' => str_replace('xs:', '', $rest->attributes->{'base'} ? $rest->attributes->{'base'}->value : null)];
            $restrictions = $rest->children;
            foreach ($restrictions as $restriction) {
                switch ($restriction->name) {
                    case 'xs:enumeration': {
                        $ret = [];
                        foreach ($this->_node->Item('xs:restriction')->Query('./*') as $enum) {
                            $ret[$enum->attributes->value->value] = $enum->attributes->title ? $enum->attributes->title->value : $enum->attributes->value->value;
                        }
                        $returnRestrictions->enumeration = $ret;
                        break;
                    }
                    case 'xs:pattern': {
                        $returnRestrictions->pattern = $restriction->attributes->value->value;
                        break;
                    }
                    case 'xs:length': {
                        $returnRestrictions->length = $restriction->attributes->value->value;
                        break;
                    }
                    case 'xs:minLength': {
                        $returnRestrictions->minLength = $restriction->attributes->value->value;
                        break;
                    }
                    case 'xs:maxLength': {
                        $returnRestrictions->maxLength = $restriction->attributes->value->value;
                        break;
                    }
                    default: {
                    }
                }
            }
            return $returnRestrictions;
        } elseif (strtolower($property) == 'attributes') {
            $attributes = [];
            $attrs = $this->_node->Query('./xs:attribute');
            if ($attrs->Count() > 0) {
                foreach ($attrs as $attr) {
                    $a = new XsdAttributeDefinition($attr, $this->_schema);
                    $attributes[$a->name] = $a;
                }
            }
            return $attributes;
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
        return (object) array(
            'name' => $this->name,
            'annotation' => $this->annotation,
            'restrictions' => $this->restrictions,
            'attributes' => $this->attributes
        );
    }

    /**
     * Returns the data as a plain object.
     *
     * @return object
     */
    public function ToObject(): object
    {
        return (object) array(
            'name' => $this->name,
            'annotation' => $this->annotation,
            'restrictions' => $this->restrictions,
            'attributes' => $this->attributes
        );
    }
}
