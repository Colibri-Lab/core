<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

use Colibri\Xml\Serialization\XmlSerialized;
use Colibri\Xml\XmlNode;

/**
 * Convert object to xml and back
 */
class XmlHelper
{
    /**
     * Encodes an object, string, or array into a string representation.
     *
     * This function takes an input value (which can be an object, string, or array) and
     * converts it into a string representation. The resulting string format may be related
     * to XML serialization, as indicated by the optional `$tag` parameter.
     *
     * @param XmlSerialized|string|array $object The input value to be encoded.
     * @param string $tag The optional tag name (default: 'object') for the encoded data.
     *
     * @return string The encoded string representation.
     */
    public static function Encode(string|array|object $object, string $tag = 'object'): string
    {
        if (is_string($object)) {
            return $object;
        }

        $ret = ['<' . $tag . '>'];
        if(!VariableHelper::IsAssociativeArray($object)) {
            foreach($object as $value) {
                $ret[] = XmlHelper::Encode($value, $tag . 'Item');
            }
        } else {
            foreach ($object as $key => $value) {
                $key = StringHelper::ToCamelCaseAttr($key);
                if (is_object($value) || is_array($value)) {
                    $ret[] = XmlHelper::Encode($value, $key);
                } else {
                    $ret[] = '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
                }
            }
        }
        $ret[] = '</' . $tag . '>';


        return implode('', $ret);
    }

    /**
     * Decodes an XML string into an XmlNode object.
     *
     * This function takes an XML string and constructs an XmlNode object representing
     * the parsed XML structure. The resulting object can be used to navigate and
     * manipulate the XML data.
     *
     * @param string $xmlString The XML string to be decoded.
     *
     * @return XmlNode An object representing the parsed XML structure.
     */
    public static function Decode(string $xmlString): XmlNode
    {
        return XmlNode::LoadNode($xmlString, 'utf-8');
    }

    /**
     * Converts an XML string or XmlNode object into an object or string representation.
     *
     * This function takes either an XML string or an XmlNode object and constructs an
     * object or string representation based on the input type. If an XmlNode object is
     * provided, it is converted to an object. If an XML string is provided, it is returned
     * as a string.
     *
     * @param string|XmlNode $xml The XML string or XmlNode object to be converted.
     *
     * @return object|string The resulting object or string representation.
     */
    public static function ToObject(string|XmlNode $xml): object|string
    {
        if(is_string($xml)) {
            $xml = XmlNode::LoadNode($xml, 'utf-8');
        }

        if($xml->elements->Count() == 0) {
            return $xml->value;
        }

        $ret = [];
        foreach($xml->Query('./*') as $node) {
            $ret[$node->name] = self::ToObject($node);
        }
        return (object)$ret;

    }

}
