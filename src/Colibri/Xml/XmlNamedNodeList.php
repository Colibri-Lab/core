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

use Colibri\Collections\ReadonlyCollection;
use DOMDocument;

/**
 * XmlNamedNodeList
 *
 * This class represents a list of nodes.
 *
 * @property-read DOMDocument $document The document.
 *
 * @method XmlNode offsetGet(mixed $offset)
 * 
 */
class XmlNamedNodeList extends ReadonlyCollection
{

    /**
     * The document.
     *
     * @var \DOMDocument
     */
    private ?DOMDocument $_document;

    /**
     * Constructor
     *
     * @param \DOMNodeList $nodelist The node list.
     * @param \DOMDocument $dom The document.
     */
    public function __construct(\DOMNodeList $nodelist, DOMDocument $dom)
    {
        $this->_document = $dom;

        $data = array();
        foreach ($nodelist as $node) {
            $data[$node->nodeName] = $node;
        }

        parent::__construct($data);
    }

    /**
     * Returns an iterator for iteration using foreach.
     *
     * @return XmlNamedNodeListIterator
     *
     */
    public function getIterator(): XmlNamedNodeListIterator
    {
        return new XmlNamedNodeListIterator($this);
    }

    /**
     * Returns the node by key.
     *
     * @param string $key The key.
     * @return XmlNode|null The node, or null if not found.
     *
     */
    public function Item(string $key): ?XmlNode
    {
        $v = parent::Item($key);
        if (is_null($v)) {
            return null;
        }
        return new XmlNode($v, $this->_document);
    }

    /**
     * Returns the node by index.
     *
     * @param int $index The index.
     * @return XmlNode The node.
     *
     */
    public function ItemAt(int $index): XmlNode
    {
        return new XmlNode(parent::ItemAt($index), $this->_document);
    }

    /**
     * Getter
     *
     * @param string $property The property.
     * @return mixed
     *
     */
    public function __get(string $property): mixed
    {
        if (strtolower($property) == 'document') {
            return $this->_document;
        }
        else {
            return parent::__get($property);
        }
    }
}
