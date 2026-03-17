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

use DOMDocument;
use DOMXPath;

/**
 * XmlReader
 *
 * This class represents a query executor for XML documents.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml
 *
 */
class XmlReader
{
    /**
     * The XML reader instance.
     *
     * @var \XMLReader
     */    
    private \XMLReader $_reader;

    /**
     * Constructor.
     *
     * @param XmlNode $node The context node.
     * @param bool $returnAsNamedMap Whether to return as a named map.
     * @param array $namespaces Optional array of namespace prefixes and URIs.
     */
    public function __construct(string $filePath)
    {
        $this->_reader = new \XMLReader();
        $this->_reader->open($filePath);
    }

    public function Each(callable $callback): void
    {
        while ($this->_reader->read()) {
            if ($this->_reader->nodeType === \XMLReader::ELEMENT) {
                $name = $this->_reader->name;
                $debth = $this->_reader->depth;
                $attrs = [];
                if ($this->_reader->hasAttributes) {
                    while ($this->_reader->moveToNextAttribute()) {
                        $attrs[$this->_reader->name] = $this->_reader->value;
                    }
                }
                $callback($this, $name, $debth, $attrs);
            }
        }
    }

    
}
