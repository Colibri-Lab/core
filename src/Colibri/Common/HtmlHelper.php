<?php

/**
 * Common
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 * @version 1.0.0
 *
 */

namespace Colibri\Common;

use Colibri\Xml\XmlNode;

/**
 * Represents a utility class for working with HTML string
 * @class
 */
class HtmlHelper
{
    /**
     * Encodes an array or object to a HTML string representation.
     *
     * @param array|object $object The input data (array or object) to be encoded.
     * @param string $tag The tag to use for the encoded data (optional, default is 'object').
     *
     * @return string The encoded data as a string.
     * @public
     * @static
     * @example
     * ```
     * $data = [
     *     'name' => 'John Doe',
     *     'age' => 30,
     *     'address' => [
     *         'street' => '123 Main St',
     *         'city' => 'Anytown',
     *         'state' => 'CA'
     *     ]
     * ];
     * $encodedData = HtmlHelper::Encode($data, 'person');
     * echo $encodedData; // Outputs the encoded HTML representation of the data
     * ```
     */
    public static function Encode(array|object $object, string $tag = 'object'): string
    {
        if (is_string($object)) {
            return $object;
        }

        $ret = ['<div class="' . $tag . '">'];
        foreach ($object as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $ret[] = HtmlHelper::Encode($value, $key);
            } else {
                $ret[] = '<div class="' . $key . '">' . $value . '</div>';
            }
        }
        $ret[] = '</div>';
        return implode('', $ret);
    }

    /**
     * Decodes an XML string and returns an XmlNode object.
     *
     * @param string $xmlString The XML string to be decoded.
     *
     * @return XmlNode The parsed XML data as an XmlNode object.
     * @public
     * @static
     * @example
     * ```
     * $xmlString = '<person><name>John Doe</name><age>30</age></person>';
     * $xmlNode = HtmlHelper::Decode($xmlString);
     * echo $xmlNode->name; // Outputs: John Doe
     * echo $xmlNode->age;  // Outputs: 30
     * ```
     */
    public static function Decode(string $xmlString): XmlNode
    {
        return XmlNode::LoadHtmlNode($xmlString, 'utf-8');
    }
}
