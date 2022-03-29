<?php

/**
 * Шифрование
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 * @version 1.0.0
 * 
 */

namespace Colibri\Encryption;

/**
 * Шифрование
 * @testFunction testCrypt
 */
class Crypt
{

    const EncryptionAlgBase64 = 'base64';
    const EncryptionAlgHex = 'hex';

    /**
     * Зашифровать
     *
     * @param string $key ключ
     * @param string $data данные
     * @param string $stringifyMethod метод превращения в строку
     * @return string
     * @testFunction testCryptEncrypt
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
     * Расшифровать
     *
     * @param string $key ключ
     * @param string $data данные
     * @param string $stringifyMethod метод превращения в строку
     * @return string
     * @testFunction testCryptDecrypt
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
