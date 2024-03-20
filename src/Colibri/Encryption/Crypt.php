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
 * Cryptography utility class.
 */
class Crypt
{
    /**
     * Encryption algorithm: Base64 encoding.
     */
    const EncryptionAlgBase64 = 'base64';

    /**
     * Encryption algorithm: Hexadecimal encoding.
     */
    const EncryptionAlgHex = 'hex';

    /**
     * Encrypts data.
     *
     * @param string $key The key.
     * @param string $data The data to encrypt.
     * @param string $stringifyMethod The method for converting to string.
     * @return string The encrypted data.
     */
    static function Encrypt(string $key, string $data, string $stringifyMethod = self::EncryptionAlgBase64): string
    {
        if (!is_string($data) || !is_string($key)) {
            return null;
        }

        $sha = hash('sha256', $key);
        $data = Rc4Crypt::Encrypt($sha, $data);
        return $stringifyMethod == self::EncryptionAlgHex ? bin2hex($data) : base64_encode($data);
    }

    /**
     * Decrypts data.
     *
     * @param string $key The key.
     * @param string $data The data to decrypt.
     * @param string $stringifyMethod The method for converting to string.
     * @return string The decrypted data.
     */
    static function Decrypt(string $key, string $data, string $stringifyMethod = self::EncryptionAlgBase64): string
    {
        if (!is_string($data) || !is_string($key)) {
            return null;
        }

        $sha = hash('sha256', $key);
        $data = $stringifyMethod == self::EncryptionAlgHex ? hex2bin($data) : base64_decode($data);
        return Rc4Crypt::Decrypt($sha, $data);
    }
}