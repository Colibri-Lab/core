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
 * XmlNodeList
 *
 * This class represents a list of XML nodes.
 *
 * @property-read \DOMDocument $document The document.
 *
 * @method XmlNode offsetGet(mixed $offset)
 */
class XmlNodeList implements \IteratorAggregate
{

    /**
     * The list of values.
     *
     * @var \DOMNodeList
     */
    private ?\DOMNodeList $_data;

    /**
     * The document.
     *
     * @var \DOMDocument
     */
    private ?\DOMDocument $_document;

    /**
     * Constructor
     *
     * @param \DOMNodeList $nodelist The list of nodes.
     * @param \DOMDocument $dom The document.
     */
    public function __construct(\DOMNodeList $nodelist, \DOMDocument $dom)
    {
        $this->_data = $nodelist;
        $this->_document = $dom;
    }

    /**
     * Returns an iterator for iteration using foreach.
     *
     * @return XmlNodeListIterator
     */
    public function getIterator(): XmlNodeListIterator
    {
        return new XmlNodeListIterator($this);
    }

    /**
     * Returns the node by index.
     *
     * @param int $index The index.
     * @return XmlNode|null The node, or null if not found.
     *
     */
    public function Item(int $index): ? XmlNode
    {
        if ($this->_data->item($index)) {
            return new XmlNode($this->_data->item($index), $this->_document);
        }
        return null;
    }

    /**
     * Getter
     *
     * @param string $property The property.
     * @return mixed The value of the property, or null if not found.
     */
    public function __get(string $property): mixed
    {
        if (strtolower($property) == 'document') {
            return $this->_document;
        }
        return null;
    }

    /**
     * Returns the count of nodes.
     *
     * @return int The count of nodes.
     */
    public function Count(): int
    {
        return $this->_data->length;
    }

    /**
     * Returns the first node.
     *
     * @return XmlNode The first node.
     */
    public function First(): XmlNode
    {
        return $this->Item(0);
    }

    /**
     * Returns the last node.
     *
     * @return XmlNode The last node.
     *
     */
    public function Last(): XmlNode
    {
        return $this->Item($this->Count() - 1);
    }

    /**
     * Removes all nodes in the collection.
     *
     * @return void
     *
     */
    public function Remove(): void
    {
        foreach ($this as $d) {
            $d->Remove();
        }
    }

    /**
     * Returns all nodes in the collection as an object.
     *
     * @param array $exclude The list of attribute and node names to exclude.
     * @param int|null $levels The number of child nodes.
     * @return array|null The nodes as an object, or null if empty.
     *
     */
    public function ToObject(array $exclude = array(), ?int $levels = null)
    {
        $ret = array();

        foreach ($this as $child) {
            if (in_array($child->name, $exclude)) {
                continue;
            }
            if (!isset($ret[$child->name])) {
                $ret[$child->name] = [];
            }
            $ret[$child->name][] = $child->ToObject($exclude, $levels);
        }

        foreach ($this as $child) {
            if (count($ret[$child->name]) == 1) {
                $ret[$child->name] = $ret[$child->name][0];
            }
        }

        if (!count($ret)) {
            return null;
        }

        return count($ret) == 1 ? $ret[array_keys($ret)[0]] : $ret;
    }
}