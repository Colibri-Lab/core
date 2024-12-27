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
 * Validate data
 */
class ValidationHelper
{
    /**
     * Validates a Bank Identifier Code (BIK).
     *
     * @param string $bik The BIK to validate.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the BIK is valid, false otherwise.
     */
    public static function ValidateBik(
        string $bik,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        $bik = (string) $bik;
        if (!$bik) {
            $error_code = 1;
            $error_message = 'БИК пуст';
        } elseif (preg_match('/[^0-9]/', $bik)) {
            $error_code = 2;
            $error_message = 'БИК может состоять только из цифр';
        } elseif (strlen($bik) !== 9) {
            $error_code = 3;
            $error_message = 'БИК может состоять только из 9 цифр';
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * Validates an Individual Taxpayer Identification Number (INN).
     *
     * @param string $inn The INN to validate.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the INN is valid, false otherwise.
     */
    public static function ValidateInn(
        string $inn,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        $inn = (string) $inn;
        if (!$inn) {
            $error_code = 1;
            $error_message = 'ИНН пуст';
        } elseif (preg_match('/[^0-9]/', $inn)) {
            $error_code = 2;
            $error_message = 'ИНН может состоять только из цифр';
        } elseif (!in_array($inn_length = strlen($inn), [10, 12])) {
            $error_code = 3;
            $error_message = 'ИНН может состоять только из 10 или 12 цифр';
        } else {
            $check_digit = function ($inn, $coefficients) {
                $n = 0;
                foreach ($coefficients as $i => $k) {
                    $n += $k * (int) $inn[$i];
                }
                return $n % 11 % 10;
            };
            switch ($inn_length) {
                case 10:
                    $n10 = $check_digit($inn, [2, 4, 10, 3, 5, 9, 4, 6, 8]);
                    if ($n10 === (int) $inn[9]) {
                        $result = true;
                    }
                    break;
                case 12:
                    $n11 = $check_digit($inn, [7, 2, 4, 10, 3, 5, 9, 4, 6, 8]);
                    $n12 = $check_digit($inn, [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8]);
                    if (($n11 === (int) $inn[10]) && ($n12 === (int) $inn[11])) {
                        $result = true;
                    }
                    break;
                default:
                    break;
            }
            if (!$result) {
                $error_code = 4;
                $error_message = 'Неправильное контрольное число';
            }
        }
        return $result;
    }

    /**
     * Validates a KPP (Tax Registration Reason Code) in Russia.
     *
     * @param string $kpp The KPP to validate.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the KPP is valid, false otherwise.
     */
    public static function ValidateKpp(
        string $kpp,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        $kpp = (string) $kpp;
        if (!$kpp) {
            $error_code = 1;
            $error_message = 'КПП пуст';
        } elseif (strlen($kpp) !== 9) {
            $error_code = 2;
            $error_message = 'КПП может состоять только из 9 знаков (цифр или заглавных букв латинского алфавита от A до Z)';
        } elseif (!preg_match('/^[0-9]{4}[0-9A-Z]{2}[0-9]{3}$/', $kpp)) {
            $error_code = 3;
            $error_message = 'Неправильный формат КПП';
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * Validates a Control Account Number (KS) using the specified Bank Identifier Code (BIK).
     *
     * @param string $ks The Control Account Number (KS) to validate.
     * @param string $bik The Bank Identifier Code (BIK) associated with the bank.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the KS is valid for the given BIK, false otherwise.
     */
    public static function ValidateKs(
        string $ks,
        string $bik,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        if (self::validateBik($bik, $error_message, $error_code)) {
            $ks = (string) $ks;
            if (!$ks) {
                $error_code = 1;
                $error_message = 'К/С пуст';
            } elseif (preg_match('/[^0-9]/', $ks)) {
                $error_code = 2;
                $error_message = 'К/С может состоять только из цифр';
            } elseif (strlen($ks) !== 20) {
                $error_code = 3;
                $error_message = 'К/С может состоять только из 20 цифр';
            } else {
                $bik_ks = '0' . substr((string) $bik, -5, 2) . $ks;
                $checksum = 0;
                foreach ([7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1] as $i => $k) {
                    $checksum += $k * ((int) $bik_ks[$i] % 10);
                }
                if ($checksum % 10 === 0) {
                    $result = true;
                } else {
                    $error_code = 4;
                    $error_message = 'Неправильное контрольное число';
                }
            }
        }
        return $result;
    }

    /**
     * Validates an OGRN (Primary State Registration Number) in Russia.
     *
     * @param string $ogrn The OGRN to validate.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the OGRN is valid, false otherwise.
     */
    public static function ValidateOgrn(
        string $ogrn,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        $ogrn = (string) $ogrn;
        if (!$ogrn) {
            $error_code = 1;
            $error_message = 'ОГРН пуст';
        } elseif (preg_match('/[^0-9]/', $ogrn)) {
            $error_code = 2;
            $error_message = 'ОГРН может состоять только из цифр';
        } elseif (strlen($ogrn) !== 13) {
            $error_code = 3;
            $error_message = 'ОГРН может состоять только из 13 цифр';
        } else {
            $n13 = (int) substr(bcsub(substr($ogrn, 0, -1), bcmul(bcdiv(substr($ogrn, 0, -1), '11', 0), '11')), -1);
            if ($n13 === (int) $ogrn[12]) {
                $result = true;
            } else {
                $error_code = 4;
                $error_message = 'Неправильное контрольное число';
            }
        }
        return $result;
    }

    /**
     * Validates an Individual Entrepreneur State Registration Number (OGRNIP) in Russia.
     *
     * @param string $ogrnip The OGRNIP to validate.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the OGRNIP is valid, false otherwise.
     */
    public static function ValidateOgrnip(
        string $ogrnip,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        $ogrnip = (string) $ogrnip;
        if (!$ogrnip) {
            $error_code = 1;
            $error_message = 'ОГРНИП пуст';
        } elseif (preg_match('/[^0-9]/', $ogrnip)) {
            $error_code = 2;
            $error_message = 'ОГРНИП может состоять только из цифр';
        } elseif (strlen($ogrnip) !== 15) {
            $error_code = 3;
            $error_message = 'ОГРНИП может состоять только из 15 цифр';
        } else {
            $n15 = (int) substr(bcsub(substr($ogrnip, 0, -1), bcmul(bcdiv(substr($ogrnip, 0, -1), '13', 0), '13')), -1);
            if ($n15 === (int) $ogrnip[14]) {
                $result = true;
            } else {
                $error_code = 4;
                $error_message = 'Неправильное контрольное число';
            }
        }
        return $result;
    }

    /**
     * Validates a Bank Account Number (RS) using the specified Bank Identifier Code (BIK).
     *
     * @param string $rs The Bank Account Number (RS) to validate.
     * @param string $bik The Bank Identifier Code (BIK) associated with the bank.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the RS is valid for the given BIK, false otherwise.
     */
    public static function ValidateRs(
        string $rs,
        string $bik,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        if (self::validateBik($bik, $error_message, $error_code)) {
            $rs = (string) $rs;
            if (!$rs) {
                $error_code = 1;
                $error_message = 'Р/С пуст';
            } elseif (preg_match('/[^0-9]/', $rs)) {
                $error_code = 2;
                $error_message = 'Р/С может состоять только из цифр';
            } elseif (strlen($rs) !== 20) {
                $error_code = 3;
                $error_message = 'Р/С может состоять только из 20 цифр';
            } else {
                $bik_rs = substr((string) $bik, -3) . $rs;
                $checksum = 0;
                foreach ([7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1] as $i => $k) {
                    $checksum += $k * ((int) $bik_rs[$i] % 10);
                }
                if ($checksum % 10 === 0) {
                    $result = true;
                } else {
                    $error_code = 4;
                    $error_message = 'Неправильное контрольное число';
                }
            }
        }
        return $result;
    }

    /**
     * Validates a Social Security Number (SNILS) in Russia.
     *
     * @param string $snils The SNILS to validate.
     * @param mixed &$error_message (Optional) A reference to store an error message if validation fails.
     * @param mixed &$error_code (Optional) A reference to store an error code if validation fails.
     * @return bool True if the SNILS is valid, false otherwise.
     */
    public static function ValidateSnils(
        string $snils,
        mixed &$error_message = null,
        mixed &$error_code = null
    ): bool {
        $result = false;
        $snils = (string) $snils;
        if (!$snils) {
            $error_code = 1;
            $error_message = 'СНИЛС пуст';
        } elseif (preg_match('/[^0-9]/', $snils)) {
            $error_code = 2;
            $error_message = 'СНИЛС может состоять только из цифр';
        } elseif (strlen($snils) !== 11) {
            $error_code = 3;
            $error_message = 'СНИЛС может состоять только из 11 цифр';
        } else {
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int) $snils[$i] * (9 - $i);
            }
            $check_digit = 0;
            if ($sum < 100) {
                $check_digit = $sum;
            } elseif ($sum > 101) {
                $check_digit = $sum % 101;
                if ($check_digit === 100) {
                    $check_digit = 0;
                }
            }
            if ($check_digit === (int) substr($snils, -2)) {
                $result = true;
            } else {
                $error_code = 4;
                $error_message = 'Неправильное контрольное число';
            }
        }
        return $result;
    }
}
