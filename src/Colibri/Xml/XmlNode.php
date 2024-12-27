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

use Colibri\AppException;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Xml\Definitions\XsdSchemaDefinition;
use Colibri\Xml\Serialization\XmlCData;
use Colibri\Xml\Serialization\XmlSerialized;
use Exception;

/**
 * Class for interacting with XML objects.
 *
 * This class provides various properties to access and manipulate XML data and elements.
 *
 * @property-read string $type The type of the XML object.
 * @property string $value The value of the XML object.
 * @property-read string $name The name of the XML object.
 * @property-read string $data The data of the XML object.
 * @property-read string $encoding The encoding of the XML object.
 * @property-read XmlNodeAttributeList $attributes The attributes of the XML object.
 * @property-read XmlNode $root The root node of the XML object.
 * @property-read XmlNode $parent The parent node of the XML object.
 * @property-read XmlNodeList $nodes The nodes of the XML object.
 * @property-read XmlNode $firstChild The first child node of the XML object.
 * @property-read XmlNodeList $elements The elements of the XML object.
 * @property-read XmlNodeList $children The children of the XML object.
 * @property-read XmlNodeList $texts The text nodes of the XML object.
 * @property \DOMDocument $document The DOMDocument associated with the XML object.
 * @property \DOMNode $raw The raw DOMNode associated with the XML object.
 * @property-read string $xml The XML string of the XML object.
 * @property-read string $innerXml The inner XML string of the XML object.
 * @property-read string $html The HTML string of the XML object.
 * @property-read string $innerHtml The inner HTML string of the XML object.
 * @property-read XmlNode $next The next sibling node of the XML object.
 * @property-read XmlNode $prev The previous sibling node of the XML object.
 * @property-write string $cdata The CDATA section of the XML object.
 * @property object $tag The tag object associated with the XML object.
 * @property-read bool $isCData Indicates whether the XML object is a CDATA section.
 * @property-read int $elementsCount The number of child elements of the XML object.
 *
 */
class XmlNode
{
    /**
     * XML declaration start string.
     *
     * This constant represents the starting string of an XML declaration,
     * including the version and encoding information.
     *
     * @var string
     */
    public const XmlStart = '<?xml version="1.0" encoding="%s"?>';

    /**
     * The raw document object.
     *
     * This property holds the raw \DOMDocument object.
     *
     * @var \DOMDocument|null
     */
    private ?\DOMDocument $_document;

    /**
     * The raw element object.
     *
     * This property holds the raw \DOMNode object representing an element.
     *
     * @var \DOMNode|null
     */
    private ?\DOMNode $_node;

    /**
     * Additional data.
     *
     * This property holds additional data associated with the XML object.
     *
     * @var object|null
     */
    private ?object $_tag;

    /**
     * Constructor.
     *
     * Initializes a new instance of the XmlNode class.
     *
     * @param \DOMNode      $node The node.
     * @param \DOMDocument|null $dom The document.
     */
    public function __construct(\DOMNode $node, ?\DOMDocument $dom = null)
    {
        $this->_node = $node;
        $this->_document = $dom;
        $this->_tag = (object) [];
    }

    /**
     * Creates an XmlNode object from a string or a file.
     *
     * This method creates an XmlNode object either from a string containing XML data
     * or from an XML file.
     *
     * @param string  $xmlFile The XML file path or XML string.
     * @param bool    $isFile  Indicates whether the provided argument is a file path or a string.
     * @return XmlNode The created XmlNode object.
     *
     */
    public static function Load(string $xmlFile, bool $isFile = true): XmlNode
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (!$isFile) {
            try {
                if (!$xmlFile) {
                    // если пустой
                    throw new AppException('Empty xml string');
                }
                $dom->loadXML($xmlFile);
            } catch (\Throwable $e) {
                throw new AppException('Error in file ' . $xmlFile . ': ' . $e->getMessage());
            }
        } else {
            if (File::Exists($xmlFile)) {
                try {
                    $dom->load($xmlFile);
                } catch (\Throwable $e) {
                    throw new AppException('Error in ' . $xmlFile . ': ' . $e->getMessage());
                }
            } else {
                throw new AppException('File ' . $xmlFile . ' does not exists');
            }
        }

        return new XmlNode($dom->documentElement, $dom);
    }

    /**
     * Creates an XmlNode from an incomplete document.
     *
     * This method creates an XmlNode object from a string containing partial XML data.
     *
     * @param string $xmlString The XML string.
     * @param string $encoding The encoding of the string (default: utf-8).
     * @return XmlNode The created XmlNode object.
     *
     */
    public static function LoadNode(string $xmlString, string $encoding = 'utf-8'): XmlNode
    {
        try {
            $dom = new \DOMDocument('1.0', $encoding);
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML((
                strstr($xmlString, '<' . '?xml') === false ?
                str_replace('%s', $encoding, self::XmlStart) : ''
            ) . $xmlString);
            return new XmlNode($dom->documentElement, $dom);
        } catch (\Throwable $e) {
            throw new AppException(
                'Error in xml data ' . (
                    (
                        strstr($xmlString, '<' . '?xml') === false ?
                    str_replace('%s', $encoding, self::XmlStart) : ''
                    ) . $xmlString
                ) . ': ' . $e->getMessage()
            );
        }
    }

    /**
     * Creates an XMLHtmlNode from an incomplete document.
     *
     * This method creates an XMLHtmlNode object from a string containing partial HTML data.
     *
     * @param string $xmlString The HTML string.
     * @param string $encoding The encoding of the string (default: utf-8).
     * @return XmlNode The created XMLHtmlNode object.
     *
     */
    public static function LoadHtmlNode(string $xmlString, string $encoding = 'utf-8'): XmlNode
    {
        try {
            $dom = new \DOMDocument('1.0', $encoding);
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            @$dom->loadHTML((
                strstr($xmlString, '<' . '?xml') === false ?
                str_replace('%s', $encoding, self::XmlStart) : ''
            ) . '<div>' . $xmlString . '</div>');
            return new XmlNode($dom->documentElement->firstChild->firstChild, $dom);
        } catch (\Throwable $e) {
            throw new AppException('Error in xml data ' . (
                (
                    strstr($xmlString, '<' . '?xml') === false ?
                str_replace('%s', $encoding, self::XmlStart) : ''
                ) . $xmlString
            ) . ': ' . $e->getMessage());
        }
    }

    /**
     * Creates an XmlNode object from an HTML string or file.
     *
     * This method creates an XmlNode object either from a string containing HTML data
     * or from an HTML file.
     *
     * @param string  $htmlFile The HTML file path or HTML string for loading.
     * @param bool    $isFile   Indicates whether the provided argument is a file path or not.
     * @param string  $encoding The encoding of the file or string (default: utf-8).
     * @return XmlNode The created XmlNode object.
     *
     */
    public static function LoadHTML(string $htmlFile, bool $isFile = true, string $encoding = 'utf-8'): XmlNode
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', $encoding);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (!$isFile) {
            try {
                $dom->loadHTML($htmlFile);
            } catch (\Throwable $e) {
                throw new AppException('Error in file ' . $htmlFile . ': ' . $e->getMessage());
            }
        } else {
            if (File::Exists($htmlFile)) {
                try {
                    $dom->loadHTMLFile($htmlFile);
                } catch (\Throwable $e) {
                    throw new AppException('Error in ' . $htmlFile . ': ' . $e->getMessage());
                }
            } else {
                throw new AppException('File ' . $htmlFile . ' does not exists');
            }
        }

        return new XmlNode($dom->documentElement, $dom);
    }

    /**
     * Saves the XML stored in the object to a file or returns it as a string.
     *
     * This method either saves the XML stored in the object to a specified file
     * or returns it as a string if no filename is provided.
     *
     * @param string $filename The path to the file for saving. If not specified, the XML string will be returned.
     * @return string|null The XML string if $filename is empty, otherwise null.
     *
     */
    public function Save(string $filename = ""): ?string
    {
        if (!empty($filename)) {
            if (!File::Exists($filename)) {
                File::Create($filename);
            }
            $this->_document->formatOutput = true;
            $this->_document->save($filename, LIBXML_NOEMPTYTAG);
            return null;
        } else {
            return $this->_document->saveXML(null, LIBXML_NOEMPTYTAG);
        }
    }

    /**
     * Exports the XML data with a specified document tag.
     *
     * This method exports the XML data with the specified document tag by creating a new XML node
     * with the provided tag and appending the current XML node to it. The method returns a new instance
     * of the XmlNode class representing the exported XML.
     *
     * @param string $documentTag The document tag to use for exporting.
     * @return self A new instance of the XmlNode class representing the exported XML.
     */
    public function Export(string $documentTag): self
    {
        $xml = XmlNode::LoadNode('<'.$documentTag.'></'.$documentTag.'>', 'utf-8');
        $xml->Append($this);
        return $xml;
    }

    /**
     * Saves the HTML stored in the object to a file or returns it as a string.
     *
     * This method either saves the HTML stored in the object to a specified file
     * or returns it as a string if no filename is provided.
     *
     * @param string $filename The path to the file for saving. If not specified, the HTML string will be returned.
     * @return string|null The HTML string if $filename is empty, otherwise null.
     *
     */
    public function SaveHTML(string $filename = ""): ?string
    {
        if (!empty($filename)) {
            $this->_document->saveHTMLFile($filename);
            return null;
        } else {
            return $this->_document->saveHTML();
        }
    }

    /**
     * Getter method.
     *
     * This method is called when attempting to access inaccessible properties of the object.
     * It returns the value of the requested property.
     *
     * @param string $property The requested property.
     * @return mixed The value of the requested property.
     *
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case 'type': {
                return $this->_node->nodeType;
            }
            case 'value': {
                return $this->_node->nodeValue;
            }
            case 'iscdata': {
                return $this->_node->firstChild instanceof \DOMCdataSection;
            }
            case 'name': {
                return $this->_node->nodeName;
            }
            case 'data': {
                return $this->_node->textContent;
            }
            case 'encoding': {
                return $this->_document->encoding ? $this->_document->encoding : 'utf-8';
            }
            case 'attributes': {
                if (!is_null($this->_node->attributes)) {
                    return new XmlNodeAttributeList($this->_document, $this->_node, $this->_node->attributes);
                } else {
                    return null;
                }
            }
            case 'root': {
                return $this->_document ? new XmlNode($this->_document->documentElement, $this->_document) : null;
            }
            case 'parent': {
                return $this->_node->parentNode ? new XmlNode($this->_node->parentNode, $this->_document) : null;
            }
            case 'nodes': {
                if ($this->_node->childNodes) {
                    return new XmlNodeList($this->_node->childNodes, $this->_document);
                } else {
                    return null;
                }
            }
            case 'firstchild': {
                return $this->_node->firstChild ? new XmlNode($this->_node->firstChild, $this->_document) : null;
            }
            case 'elements': {
                return $this->Query('./child::*', true);
            }
            case 'children': {
                return $this->Query('./child::*');
            }
            case 'texts': {
                return $this->Query('./child::text()');
            }
            case 'elementscount': {
                $xp = new \DOMXPath($this->_document);
                return $xp->evaluate('count(./child::*)', $this->_node);
            }
            case 'index': {
                $xp = new \DOMXPath($this->_document);
                return $xp->evaluate('count(preceding-sibling::*)', $this->_node);
            }
            case 'document': {
                return $this->_document;
            }
            case 'raw': {
                return $this->_node;
            }
            case 'xml': {
                return $this->_document->saveXML($this->_node, LIBXML_NOEMPTYTAG);
            }
            case 'innerxml': {
                $data = $this->_document->saveXML($this->_node, LIBXML_NOEMPTYTAG);
                $data = preg_replace('/<' . $this->name . '[^>]*>/im', '', $data);
                return preg_replace('/<\/' . $this->name . '[^>]*>/im', '', $data);
            }
            case 'html': {
                return $this->_document->saveHTML($this->_node);
            }
            case 'innerhtml': {
                $data = $this->_document->saveHTML($this->_node);
                $data = preg_replace('/<' . $this->name . '[^>]*>/im', '', $data);
                return preg_replace('/<\/' . $this->name . '[^>]*>/im', '', $data);
            }
            case 'next': {
                return $this->_node->nextSibling ? new XmlNode($this->_node->nextSibling, $this->_document) : null;
            }
            case 'prev': {
                return $this->_node->previousSibling ? new XmlNode($this->_node->previousSibling, $this->_document) : null;
            }
            case 'tag': {
                return $this->_tag;
            }
            default: {
                $item = $this->Item($property);
                if (is_null($item)) {
                    $items = $this->getElementsByName($property);
                    if ($items->Count() > 0) {
                        $item = $items->First();
                    } else {
                        if ($this->type == 1) {
                            $item = $this->attributes->$property;
                        }
                    }
                }
                return $item;
            }
        }
    }

    /**
     * Returns the path based on the query.
     *
     * This method returns the path by querying each parent for data.
     *
     * @param string $query The query to each parent for returning data.
     * @return string The path determined by the query.
     */
    public function Path(string $query): string
    {

        $ret = [];
        $parents = $this->Query('./ancestor-or-self::*');
        foreach ($parents as $parent) {
            $queryNode = $parent->Query($query);
            if ($queryNode->Count() > 0) {
                $ret[] = $queryNode->First()->value;
            }
        }

        return implode('/', $ret);
    }

    /**
     * Setter method.
     *
     * This method is called when attempting to set inaccessible properties of the object.
     * It sets the value of the specified property.
     *
     * @param string $property The property to be set.
     * @param mixed $value The value to be assigned to the property.
     * @return void
     *
     */
    public function __set(string $property, mixed $value): void
    {
        switch (strtolower($property)) {
            case 'value': {
                $this->_node->nodeValue = $value;
                break;
            }
            case 'cdata': {
                $this->_node->appendChild($this->_document->createCDATASection($value));
                break;
            }
            case 'raw': {
                $this->_node = $value;
                break;
            }
            case 'document': {
                $this->_document = $value;
                break;
            }
            case 'tag': {
                $this->_tag = $value;
                break;
            }
            default: {
                break;
            }
        }
    }

    /**
     * Returns an XmlNode object corresponding to the child object with the name $name.
     *
     * This method returns an XmlNode object that corresponds to the child node with the specified $name.
     *
     * @param string $name The name of the child node.
     * @return XmlNode|null An XmlNode object corresponding to the specified child node, or null if not found.
     *
     */
    public function Item(string $name): ?XmlNode
    {
        $list = $this->Items($name);
        if ($list->Count() > 0) {
            return $list->First();
        } else {
            return null;
        }
    }

    /**
     * Returns an XmlNodeList with the tag name $name.
     *
     * This method returns an XmlNodeList containing all child nodes with the specified tag name.
     *
     * @param string $name The tag name of the child nodes.
     * @return XmlNodeList An XmlNodeList containing child nodes with the specified tag name.
     *
     */
    public function Items(string $name): XmlNodeList
    {
        return $this->Query('./child::' . $name);
    }

    /**
     * Checks if the current node is a child of the specified node.
     *
     * This method checks if the current node is a child of the specified node.
     *
     * @param XmlNode $node The node to check against.
     * @return bool True if the current node is a child of the specified node, false otherwise.
     *
     */
    public function IsChildOf(XmlNode $node): bool
    {
        $p = $this;
        while ($p->parent) {
            if ($p->raw === $node->raw) {
                return true;
            }
            $p = $p->parent;
        }
        return false;
    }

    /**
     * Adds the specified nodes/node to the end.
     *
     * This method adds the specified nodes or node to the end of the current node.
     *
     * @param mixed $nodes The nodes or node to append.
     * @return void
     *
     */
    public function Append(mixed $nodes): void
    {
        if (VariableHelper::IsNull($nodes)) {
            return;
        }

        if ($nodes instanceof XmlNode) {
            if ($nodes->name == 'html') {
                if ($nodes->{'body'}) {
                    $nodes = $nodes->{'body'};
                    if ($nodes->children->Count() > 0) {
                        foreach ($nodes->children as $node) {
                            $node->raw = $this->_document->importNode($node->raw, true);
                            $node->document = $this->_document;
                            $this->_node->appendChild($node->raw);
                        }
                    } else {
                        $nodes->raw = $this->_document->importNode($nodes->raw, true);
                        $nodes->document = $this->_document;
                        $this->_node->appendChild($nodes->raw);
                    }
                } elseif ($nodes->{'head'}) {
                    $nodes = $nodes->{'head'};
                    $nodes->raw = $this->_document->importNode($nodes->raw, true);
                    $nodes->document = $this->_document;
                    $this->_node->appendChild($nodes->raw);
                }
            } else {
                $nodes->raw = $this->_document->importNode($nodes->raw, true);
                $nodes->document = $this->_document;
                $this->_node->appendChild($nodes->raw);
            }
        } elseif ($nodes instanceof XmlNodeList || is_array($nodes)) {
            foreach ($nodes as $node) {

                if ($node->name == 'html') {
                    if ($node->body) {
                        $node = $node->body;
                        if ($node->children->Count() > 0) {
                            foreach ($node->children as $n) {
                                $n->raw = $this->_document->importNode($n->raw, true);
                                $n->document = $this->_document;
                                $this->_node->appendChild($n->raw);
                            }
                        } else {
                            $node->raw = $this->_document->importNode($node->raw, true);
                            $node->document = $this->_document;
                            $this->_node->appendChild($node->raw);
                        }
                    } elseif ($node->head) {
                        $node = $node->head;
                        $node->raw = $this->_document->importNode($node->raw, true);
                        $node->document = $this->_document;
                        $this->_node->appendChild($node->raw);
                    }
                } else {
                    $node->raw = $this->_document->importNode($node->raw, true);
                    $node->document = $this->_document;
                    $this->_node->appendChild($node->raw);
                }
            }
        }
    }

    /**
     * Adds the specified nodes/node before the $relation node.
     *
     * This method adds the specified nodes or node before the specified $relation node.
     *
     * @param mixed $nodes The nodes or node to insert.
     * @param XmlNode $relation The node before which to insert.
     * @return void
     *
     */
    public function Insert(mixed $nodes, XmlNode $relation): void
    {
        if ($nodes instanceof XmlNode) {
            $nodes->raw = $this->_document->importNode($nodes->raw, true);
            $nodes->document = $this->_document;
            $this->_node->insertBefore($nodes->raw, $relation->raw);
        } elseif ($nodes instanceof XmlNodeList) {
            foreach ($nodes as $node) {
                $node->raw = $this->_document->importNode($node->raw, true);
                $node->document = $this->_document;
                $this->_node->insertBefore($node->raw, $relation->raw);
            }
        }
    }

    /**
     * Removes the current node.
     *
     * This method removes the current node from its parent node.
     *
     * @return void
     *
     */
    public function Remove(): void
    {
        if ($this->_node->parentNode) {
            $this->_node->parentNode->removeChild($this->_node);
        }
    }

    /**
     * Replaces the current node with the specified node.
     *
     * This method replaces the current node with the specified node.
     *
     * @param XmlNode $node The node to replace with.
     * @return void
     *
     */
    public function ReplaceTo(XmlNode $node): void
    {
        $__node = $node->raw;
        $__node = $this->_document->importNode($__node, true);
        $this->_node->parentNode->replaceChild($__node, $this->_node);
        $this->_node = $__node;
    }

    /**
     * Returns elements with an attribute @name containing the specified name.
     *
     * This method returns elements with an attribute @name containing the specified name.
     *
     * @param string $name The name of the attribute.
     * @return XmlNamedNodeList The list of nodes.
     *
     */
    public function getElementsByName(string $name): XmlNamedNodeList
    {
        return $this->Query('./child::*[@name="' . $name . '"]', true);
    }

    /**
     * Creates a text node with the specified content.
     *
     * This method creates a text node with the specified content.
     *
     * @param mixed $string The content of the text node.
     * @return XmlNode The created text node.
     */
    public function CreateTextNode($string): XmlNode
    {
        return new XmlNode($this->_document->createTextNode($string), $this->_document);
    }

    /**
     * Executes an XPath query.
     *
     * This method executes the specified XPath query and returns the result as either
     * an XmlNodeList or an XmlNamedNodeList.
     *
     * @param string $query The XPath query string.
     * @param bool $returnAsNamedMap Whether to return the result as a named map.
     * @param array $namespaces An array of namespace prefixes and URIs.
     * @return XmlNodeList|XmlNamedNodeList The result of the XPath query.
     *
     */
    public function Query(
        string $query,
        bool $returnAsNamedMap = false,
        array $namespaces = []
    ): XmlNodeList|XmlNamedNodeList {
        $xq = new XmlQuery($this, $returnAsNamedMap, $namespaces);
        return $xq->Query($query);
    }

    /**
     * Converts the current node and its children into an XmlSerialized object.
     *
     * This method converts the current node and its children into an XmlSerialized object,
     * optionally excluding specified attributes and nodes and limiting the number of levels to be included.
     *
     * @param array $exclude An array of attribute and node names to exclude.
     * @param int|null $levels The number of levels to include.
     * @return XmlSerialized|XmlCData|string|null The converted XmlSerialized object, XmlCData, string, or null.
     *
     */
    public function ToObject(
        array $exclude = [],
        ?int $levels = null
    ): XmlSerialized|XmlCData|string|null {

        if ($exclude == null) {
            $exclude = [];
        }

        if ($this->attributes->Count() == 0 && $this->children->Count() == 0) {
            if ($this->isCData) {
                return new XmlCData($this->value);
            } else {
                return $this->value;
            }
        }

        $attributes = [];
        $content = [];

        foreach ($this->attributes as $attr) {
            $excluded = false;
            if (is_array($exclude)) {
                $excluded = in_array($attr->name, $exclude);
            } elseif (is_callable($exclude)) {
                $excluded = $exclude($this, $attr);
            }
            if (!$excluded) {
                $attributes[$attr->name] = $attr->value;
            }
        }

        if ($this->children->Count() == 0) {
            if ($this->isCData) {
                $content = new XmlCData($this->value);
            } else {
                $content = $this->value;
            }
        } else {
            $content = [];
            if (is_null($levels) || $levels > 0) {
                $children = $this->children;
                foreach ($children as $child) {

                    $excluded = false;
                    if (is_array($exclude)) {
                        $excluded = in_array($child->name, $exclude);
                    } elseif (is_callable($exclude)) {
                        $excluded = $exclude($this, $child);
                    }

                    if ($excluded) {
                        continue;
                    }

                    $content[] = (object) [$child->name => $child->ToObject($exclude, is_null($levels) ? null : $levels - 1)];
                }
            }
        }

        if (is_array($content)) {
            $retContent = [];
            foreach ($content as $item) {
                $itemArray = get_object_vars($item);
                $key = array_keys($itemArray)[0];
                if (!isset($retContent[$key])) {
                    $retContent[$key] = [];
                }
                $retContent[$key][] = $item->$key;
            }

            foreach ($retContent as $key => $value) {
                if (count($value) == 1) {
                    $retContent[$key] = reset($value);
                }
            }

            $content = $retContent;
        }

        if (!count($attributes) && is_array($content) && !count($content)) {
            return null;
        }

        return new XmlSerialized($this->name, $attributes, $content);
    }

    /**
     * Restores a node from an XmlSerialized object.
     *
     * This method restores a node from the specified XmlSerialized object,
     * optionally using the provided element definition for orientation.
     *
     * @param XmlSerialized $xmlSerializedObject The object to restore from.
     * @param mixed|null $elementDefinition The definition of elements to orient towards.
     * @return XmlNode The restored XmlNode object.
     *
     */
    public static function FromObject(
        XmlSerialized $xmlSerializedObject,
        mixed $elementDefinition = null
    ): XmlNode {

        $xml = XmlNode::LoadNode('<' . $xmlSerializedObject->name . ' />', 'utf-8');
        foreach ($xmlSerializedObject->attributes as $name => $value) {

            $attributeDefinition = $elementDefinition && isset($elementDefinition->attributes[$name]) ? $elementDefinition->attributes[$name] : null;

            if ($attributeDefinition) {
                $baseType = $attributeDefinition->type->restrictions->base;
                // есть специфика только, если это булево значение
                if ($baseType == 'boolean') {
                    $value = $value ? 'true' : 'false';
                }
            }

            $xml->attributes->Append($name, $value);
        }

        if ($xmlSerializedObject->content) {
            if ($xmlSerializedObject->content instanceof XmlCData) {
                $xml->cdata = $xmlSerializedObject->content->value;
            } else {
                foreach ($xmlSerializedObject->content as $name => $element) {
                    if (!is_array($element)) {
                        $element = [$element];
                    }
                    foreach ($element as $el) {
                        if (is_string($el)) {
                            $xml->Append(XmlNode::LoadNode('<' . $name . '>' . $el . '</' . $name . '>'));
                        } elseif ($el instanceof XmlCData) {
                            $xml->Append(XmlNode::LoadNode('<' . $name . '><![CDATA[' . $el->value . ']]></' . $name . '>'));
                        } elseif ($el instanceof XmlSerialized && isset($elementDefinition->elements[$name])) {
                            $xml->Append(XmlNode::FromObject($el, $elementDefinition->elements[$name]));
                        }
                    }
                }
            }
        }


        return $xml;
    }
}
