<?php

/**
 * Обьект в html и обратно
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
 * Обьект в html и обратно
 */
class HtmlHelper
{

    /**
     * Обькет в xml
     *
     * @param mixed $object
     * @param string $tag
     * @return string
     * @testFunction testHtmlHelperEncode
     */
    public static function Encode($object, $tag = 'object')
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
     * Строка в xml
     *
     * @param string $xmlString
     * @return XmlNode
     * @testFunction testHtmlHelperDecode
     */
    public function Decode($xmlString)
    {
        return XmlNode::LoadHtmlNode($xmlString, 'utf-8');
    }
}
