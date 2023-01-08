<?php

/**
 * Обьект в xml и обратно
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 * @version 1.0.0
 * 
 */
namespace Colibri\Common;

use Colibri\Xml\Serialization\XmlSerialized;
use Colibri\Xml\XmlNode;

/**
 * Обьект в xml и обратно
 */
class XmlHelper
{

    /**
     * Обькет в xml
     *
     * @param XmlSerialized|string $object
     * @param string $tag
     * @return string
     * @testFunction testXmlHelperEncode
     */
    public static function Encode(XmlSerialized|string $object, string $tag = 'object'): string
    {
        if (is_string($object)) {
            return $object;
        }

        $ret = ['<' . $tag . '>'];
        foreach ($object as $key => $value) {
            $key = StringHelper::ToCamelCaseAttr($key);
            if (is_object($value) || is_array($value)) {
                $ret[] = XmlHelper::Encode($value, $key);
            } else {
                $ret[] = '<' . $key . '>' . $value . '</' . $key . '>';
            }
        }
        $ret[] = '</' . $tag . '>';


        return implode('', $ret);
    }

    /**
     * Строка в xml
     *
     * @param string $xmlString
     * @return XmlNode
     * @testFunction testXmlHelperDecode
     */
    public static function Decode(string $xmlString): XmlNode
    {
        return XmlNode::LoadNode($xmlString, 'utf-8');
    }

}