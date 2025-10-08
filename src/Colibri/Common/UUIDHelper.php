<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

use InvalidArgumentException;

/**
 * Two Factor Generator Helper      
 */
class UUIDHelper
{
    // Версия 1 — на основе времени и MAC (если нет MAC, сгенерируем случайно)
    public static function v1()
    {
        $time = microtime(true) * 10000000 + 0x01B21DD213814000;
        $timeHex = sprintf('%016x', (int)$time);

        $clockSeq = random_int(0, 0x3fff);
        $node = random_int(0, 0xffffffffffff); // если MAC неизвестен

        return sprintf('%08s-%04s-1%03s-%02x%02x-%012s',
            substr($timeHex, 0, 8),
            substr($timeHex, 8, 4),
            substr($timeHex, 12, 3),
            ($clockSeq >> 8) & 0xff,
            $clockSeq & 0xff,
            str_pad(dechex($node), 12, '0', STR_PAD_LEFT)
        );
    }

    // Версия 3 — хэш MD5 от namespace + имя
    public static function v3($namespace, $name)
    {
        return self::nameBasedUuid($namespace, $name, 'md5', 3);
    }

    // Версия 4 — полностью случайная
    public static function v4()
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // версия 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // вариант
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Версия 5 — хэш SHA1 от namespace + имя
    public static function v5($namespace, $name)
    {
        return self::nameBasedUuid($namespace, $name, 'sha1', 5);
    }

    // Вспомогательная функция для v3 и v5
    private static function nameBasedUuid($namespace, $name, $hashFunc, $version)
    {
        if (!self::isValid($namespace)) {
            throw new InvalidArgumentException('Invalid namespace UUID');
        }

        $nhex = str_replace(['-', '{', '}'], '', $namespace);
        $nstr = '';
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        $hash = hash($hashFunc, $nstr . $name);
        $timeLow = substr($hash, 0, 8);
        $timeMid = substr($hash, 8, 4);
        $timeHi = substr($hash, 12, 4);
        $clockSeqHi = substr($hash, 16, 2);
        $clockSeqLow = substr($hash, 18, 2);
        $node = substr($hash, 20, 12);

        $timeHi = dechex((hexdec($timeHi) & 0x0fff) | ($version << 12));
        $clockSeqHi = dechex((hexdec($clockSeqHi) & 0x3f) | 0x80);

        return sprintf('%08s-%04s-%04s-%02s%02s-%012s',
            $timeLow,
            $timeMid,
            $timeHi,
            $clockSeqHi,
            $clockSeqLow,
            $node
        );
    }

    // Проверка UUID на корректность
    public static function isValid($uuid)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }
}