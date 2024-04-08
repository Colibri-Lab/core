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

use Colibri\Common\StringHelper;
use Colibri\Utils\Debug;

/**
 * XmlNodeAttributeList
 *
 * This class represents a list of attributes associated with a XML node.
 *
 * @property-read int $count The count of attributes.
 *
 * @method XmlAttribute offsetGet(mixed $offset)
 */
class XmlNodeAttributeList implements \IteratorAggregate, \Countable, \ArrayAccess
{

    /**
     * The document.
     *
     * @var \DOMDocument
     */
    private ?\DOMDocument $_document;

    /**
     * The node.
     *
     * @var mixed
     */
    private mixed $_node;

    /**
     * The list of attributes.
     *
     * @var \DOMNamedNodeMap
     */
    private ?\DOMNamedNodeMap $_data;

    /**
     * Constructor
     *
     * @param \DOMDocument $document The document.
     * @param \DOMNode $node The node.
     * @param \DOMNamedNodeMap $xmlattributes The list of attributes.
     */
    public function __construct(\DOMDocument $document, \DOMNode $node, \DOMNamedNodeMap $xmlattributes)
    {
        $this->_document = $document;
        $this->_node = $node;
        $this->_data = $xmlattributes;
    }

    /**
     * Returns an iterator for iteration using foreach.
     *
     * @return XmlNodeListIterator
     *
     */
    public function getIterator(): XmlNodeListIterator
    {
        return new XmlNodeListIterator($this);
    }

    /**
     * Returns the attribute by index.
     *
     * @param int $index The index.
     * @return XmlAttribute The attribute.
     *

     */
    public function Item(int $index): XmlAttribute
    {
        return new XmlAttribute($this->_data->item($index));
    }

    /**
     * Returns the count of attributes.
     *
     * @return int The count of attributes.
     *
     */
    public function Count(): int
    {
        return $this->_data->length;
    }

    /**
     * Getter
     *
     * @param string $property The property.
     * @return XmlAttribute|null The attribute, or null if not found.
     *
     */
    public function __get(string $property): mixed
    {
        $attr = $this->_data->getNamedItem($property);
        if (!is_null($attr)) {
            return new XmlAttribute($attr);
        }

        $property = StringHelper::FromCamelCaseAttr($property);
        $attr = $this->_data->getNamedItem($property);
        if (!is_null($attr)) {
            return new XmlAttribute($attr);
        }
        return null;
    }

    /**
     * Appends an attribute.
     *
     * @param string $name The name of the attribute.
     * @param string $value The value of the attribute.
     * @return void
     *
     */
    public function Append(string $name, string $value): void
    {
        $attr = $this->_document->createAttribute($name);
        $attr->value = $value;
        $this->_node->appendChild($attr);
    }

    /**
     * Removes an attribute by name.
     *
     * @param string $name The name of the attribute.
     * @return void
     *
     */
    public function Remove(string $name): void
    {
        if ($this->$name && $this->$name->raw) {
            $this->_node->removeAttributeNode($this->$name->raw);
        }
    }

    /**
     * Sets the value by index.
     *
     * @param mixed $offset The offset.
     * @param mixed $value The value.
     * @return void
     *
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->Append($offset, $value);
    }

    /**
     * Checks if data exists by index.
     *
     * @param int $offset The offset.
     * @return bool Whether the data exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset < $this->Count();
    }

    /**
     * Unsets data by index.
     *
     * @param string $offset The offset.
     * @return void
     *
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->Remove($offset);
    }

    /**
     * Retrieves the value by index.
     *
     * @param int $offset The offset.
     * @return mixed The value.
     *
     */
    public function offsetGet(mixed $offset): mixed
    {
        return is_numeric($offset) ? $this->Item($offset) : $this->$offset;
    }



}
