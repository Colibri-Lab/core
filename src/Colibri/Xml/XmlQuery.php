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

use DOMXPath;

/**
 * XmlQuery
 *
 * This class represents a query executor for XML documents.
 * 
 * @class
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml
 *
 */
class XmlQuery
{
    /**
     * The context node for the query.
     * @private
     * @var XmlNode
     */
    private ?XmlNode $_contextNode;

    /**
     * The XPath operator.
     * @private
     * @var DOMXPath
     */
    private ?DOMXPath $_operator;

    /**
     * Indicates whether to return results as a named map or a simple list.
     * @private
     * @var bool
     */
    private bool $_returnAsNamedMap;

    /**
     * Constructor.
     * @public
     * @constructor
     * @param XmlNode $node The context node.
     * @param bool $returnAsNamedMap Whether to return as a named map.
     * @param array $namespaces Optional array of namespace prefixes and URIs.
     */
    public function __construct(XmlNode $node, bool $returnAsNamedMap = false, array $namespaces = [])
    {
        $this->_returnAsNamedMap = $returnAsNamedMap;
        $this->_contextNode = $node;
        $this->_operator = new DOMXPath($this->_contextNode->document);
        if(!empty($namespaces)) {
            foreach($namespaces as $prefix => $namespace) {
                $this->_operator->registerNamespace($prefix, $namespace);
            }
        }
    }

    /**
     * Executes the XPath query.
     * @public
     * @param string $xpathQuery The XPath query string.
     * @return XmlNodeList|XmlNamedNodeList The result node list.
     */
    public function Query(string $xpathQuery): XmlNodeList|XmlNamedNodeList
    {
        $res = $this->_operator->query($xpathQuery, $this->_contextNode->raw);
        if (!$res) {
            return new XmlNamedNodeList(new \DOMNodeList(), $this->_contextNode->document);
        }
        if ($this->_returnAsNamedMap) {
            return new XmlNamedNodeList($res, $this->_contextNode->document);
        }
        return new XmlNodeList($res, $this->_contextNode->document);
    }
}
