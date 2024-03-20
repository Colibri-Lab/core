<?php

/**
* Encryption
*
* @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
* @copyright 2019 ColibriLab
* @package Colibri\Data\Storages
*/

namespace Colibri\Encryption;

/**
 * RC4 encryption.
 */
class Rc4Crypt
{

    /**
     * Encrypts data using RC4 algorithm.
     *
     * @param string $pwd The encryption key.
     * @param string $data The data to encrypt.
     * @return string The encrypted data.
     */
    static function Encrypt(string $pwd, string $data): string
    {
        $key[] = '';
        $box[] = '';
        $cipher = '';
        $pwd_length = strlen($pwd);
        $data_length = strlen($data);

        for ($i = 0; $i < 256; $i++) {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        $a = $j = 0;
        for ($i = 0; $i < $data_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;

            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return $cipher;
    }

    /**
     * Decrypts data using RC4 algorithm.
     *
     * @param string $pwd The encryption key.
     * @param string $data The data to decrypt.
     * @return string The decrypted data.
     */
    static function Decrypt(string $pwd, string $data): string
    {
        return rc4crypt::encrypt($pwd, $data);
    }
}