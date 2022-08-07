<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 */

namespace Colibri\Common;
use Colibri\Utils\Debug;

class NoLangHelper
{
    
    public static function ParseString(string $value): string
    {
        $res = preg_match_all('/#\{(.*?)\}/i', $value, $matches, PREG_SET_ORDER);
        if($res > 0) {
            foreach($matches as $match) {
                $parts = explode(';', $match[1]);
                $lang = $parts[0];
                $default = $parts[1] ?? '';
                $replaceWith = $default;
                $value = str_replace($match[0], str_replace('"', '&quot;', str_replace('\'', '`', $replaceWith)), $value);
            }
        }
        return $value;
    }

    public static function ParseArray(array|object $array): array
    {
        $ret = [];
        foreach($array as $key => $value) {
            if($value instanceof \DateTime) {
                $ret[$key] = $value;
                continue;
            }
            if(is_array($value)) {
                $ret[$key] = self::ParseArray($value);
            }
            else if(is_object($value)) {
                if(method_exists($value, 'ToArray')) {
                    $value = $value->ToArray();
                }
                $ret[$key] = self::ParseArray($value);
            }
            else {
                if(is_string($value)) {
                    $value = self::ParseString($value);
                }
                $ret[$key] = $value;
            }
        }
        return $ret;
    }
}