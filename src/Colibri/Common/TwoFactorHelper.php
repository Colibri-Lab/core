<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

/**
 * Two Factor Generator Helper
 * @class      
 */
class TwoFactorHelper
{
    /**
     * Encodes binary data into a Base32 string.
     *
     * @param string $data The binary data to encode.
     * @return string The Base32 encoded string.
     * @public
     * @static
     */
    private static function encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($data) as $c) {
            $binary .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $binary = str_split($binary, 5);
        $base32 = '';
        foreach ($binary as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $base32 .= $alphabet[bindec($chunk)];
        }
        return $base32;
    }

    /**
     * Decodes a Base32 string into binary data.
     *
     * @param string $b32 The Base32 encoded string.
     * @return string The decoded binary data.
     * @public
     * @static
     */
    private static function decode(string $b32): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper($b32);
        $binary = '';
        foreach (str_split($b32) as $char) {
            $index = strpos($alphabet, $char);
            if ($index === false) continue;
            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }
        $bytes = str_split($binary, 8);
        $result = '';
        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $result .= chr(bindec($byte));
            }
        }
        return $result;
    }

    /**
     * Generates a random secret key for two-factor authentication.
     *
     * @param int $length The length of the secret key (default: 16).
     * @return string The generated secret key.
     * @public
     * @static
     */
    public static function Generate(int $length = 16) {
        $bytes = random_bytes($length);
        return self::encode($bytes);
    }

    /**
     * Generates a one-time password (OTP) based on the provided secret key and time slice.
     *
     * @param string $secret The secret key.
     * @param int|null $time_slice The time slice (default: current time slice).
     * @return string The generated OTP.
     * @public
     * @static
     */
    public static function Degenerate(string $secret, ?int $time_slice = null): string {
        if ($time_slice === null) {
            $time_slice = floor(time() / 30);
        }

        $secret = self::decode($secret);
        $time = pack('N*', 0) . pack('N*', $time_slice);

        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = substr($hash, $offset, 4);

        $code = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;
        $code = $code % 1000000;

        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifies a one-time password (OTP) against the provided secret key.
     *
     * @param string $secret The secret key.
     * @param string $code The OTP code to verify.
     * @param int|null $time_slice The time slice (default: current time slice).
     * @return bool True if the OTP is valid, false otherwise.
     * @public
     * @static
     */
    public static function Verify(string $secret, string $code, ?int $time_slice = null): bool {
        $valid = false;

        // Проверяем текущее и ±1 шаг (±30 сек)
        for ($i = -1; $i <= 1; $i++) {
            if (self::Degenerate($secret, floor(time() / 30) + $i) === $code) {
                $valid = true;
                break;
            }
        }

        return $valid;
    }


}
