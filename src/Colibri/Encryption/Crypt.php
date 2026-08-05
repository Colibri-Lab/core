<?php

/**
 * Encryption
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Encryption
 */

namespace Colibri\Encryption;

/**
 * Cryptography utility class.
 * @class
 */
class Crypt
{
    /**
     * Encryption algorithm: Base64 encoding.
     * @const string
     * @public
     */
    public const EncryptionAlgBase64 = 'base64';

    /**
     * Encryption algorithm: Hexadecimal encoding. 
     * @const string
     * @public
     */
    public const EncryptionAlgHex = 'hex';

    /**
     * Encrypts data.
     *
     * @param string $key The key.
     * @param string $data The data to encrypt.
     * @param string $stringifyMethod The method for converting to string.
     * @return string The encrypted data.
     * @static
     * @public
     * @example
     * ```
     * $key = 'my_secret_key';
     * $data = 'Hello, World!';
     * $encryptedData = Crypt::Encrypt($key, $data, Crypt::EncryptionAlgBase64);
     * echo $encryptedData; // Outputs the encrypted data in Base64 format
     * ```
     */
    public static function Encrypt(string $key, string $data, string $stringifyMethod = self::EncryptionAlgBase64): string
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
     * @static
     * @public
     * @example
     * ```
     * $key = 'my_secret_key';
     * $data = 'U2FsdGVkX1+5Z3JpZ2FyeWFu'; // Encrypted data
     * $decryptedData = Crypt::Decrypt($key, $data, Crypt::EncryptionAlgBase64);
     * echo $decryptedData; // Outputs the original data
     * ```
     */
    public static function Decrypt(string $key, string $data, string $stringifyMethod = self::EncryptionAlgBase64): ?string
    {
        if (!\is_string($data) || !\is_string($key)) {
            return null;
        }

        $sha = hash('sha256', $key);
        $data = $stringifyMethod == self::EncryptionAlgHex ? hex2bin($data) : base64_decode($data);
        return Rc4Crypt::Decrypt($sha, $data);
    }
}
