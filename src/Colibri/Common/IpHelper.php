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
 * @class
 */
class IpHelper
{
    /**
     * Check if the IP is in the patterns list
     * @param string|array $patterns patterns of IP, for example: 192.*.*.*;10.5.*.*
     * @param string $checkIp
     * @return void
     * @public
     * @static
     * @example
     * ```
     * $patterns = '192.*.*.*;10.5.*.*';
     * $checkIp = '192.168.1.1';
     * $isInPattern = IpHelper::CheckIfInPattern($patterns, $checkIp);
     * var_dump($isInPattern); // Outputs: true or false
     * ```
     */
    public static function CheckIfInPattern(string|array $patterns, string $checkIp): bool
    {
        if(is_string($patterns)) {
            $patterns = explode(';', $patterns);
        }

        foreach($patterns as $pattern) {
            $pattern = str_replace('*', '\d+', $pattern);
            $pattern = str_replace('.', '\.', $pattern);
            if(preg_match('/^' . $pattern . '$/', $checkIp)) {
                return true;
            }
        }

        return false;
    }

}
