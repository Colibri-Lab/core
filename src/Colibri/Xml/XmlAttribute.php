<?php

/**
 * Xml
 *
 * This class represents a query executor for XML documents.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml
 *
 */
namespace Colibri\Xml;

/**
 * XmlAttribute
 *
 * This class represents an XML attribute.
 *
 * @property string $value The value of the attribute.
 * @property-read string $name The name of the attribute.
 * @property-read string $type The type of the attribute.
 * @property-read \DOMNode $raw The raw node.
 *
 */
class XmlAttribute
{

    /**
     * The object containing the DOMNode of the attribute.
     *
     * @var mixed
     */
    private mixed $_data;

    /**
     * Constructor
     *
     * @param \DOMNode $data The raw node for initializing the wrapper.
     */
    public function __construct(\DOMNode $data)
    {
        $this->_data = $data;
    }

    /**
     * Getter
     *
     * @param string $property The name of the property.
     * @return mixed
     *
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case 'value':
                return $this->_data->nodeValue;
            case 'name':
                return $this->_data->nodeName;
            case 'type':
                return $this->_data->nodeType;
            case 'raw':
                return $this->_data;
            default:
                break;
        }
        return null;
    }

    /**
     * Setter
     *
     * @param string $property The name of the property.
     * @param string $value The value of the property.
     * @return void
     *
     */
    public function __set(string $property, mixed $value): void
    {
        if (strtolower($property) == 'value') {
            $this->_data->nodeValue = $value;
        }
    }

    /**
     * Removes the attribute.
     *
     * @return void
     *
     */
    public function Remove(): void
    {
        $this->_data->parentNode->removeAttributeNode($this->_data);
    }
}