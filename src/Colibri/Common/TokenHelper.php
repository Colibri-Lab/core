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

/**
 * Represents a utility class for working with HTML string
 */
class TokenHelper
{
    /**
     * Generates a token
     * @param string $key key for token 
     * @param int $ttl time to live in seconds
     * @return string
     */
    public static function Generate(string $key, int $ttl = 300): string
    {
        $time = time();
        $expire = $time + $ttl;
        $token = hash_hmac('sha256', $key . $expire, $key);
        return base64_encode($token . '|' . $expire);
    }

    public static function Validate($token, $key): bool
    {
        $token = base64_decode($token);
        if(!$token) {
            return false;
        }
        $token = explode('|', $token);
        if(count($token) !== 2) {
            return false;
        }
        $expire = (int)$token[1];
        if($expire < time()) {
            return false;
        }
        $t = hash_hmac('sha256', $key . $expire, $key);
        return $t === $token[0];
    }

}
