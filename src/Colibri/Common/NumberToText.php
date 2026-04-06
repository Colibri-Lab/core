<?php
/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

use Exception;

/**
 * Utility class for work with numbers
 */
class NumberToText
{
    public static function convert($num, $lang = 'ru'): string
    {
        switch ($lang) {
            case 'ru': return self::ru($num);
            case 'en': return self::en($num);
            case 'hy': return self::hy($num);
            case 'it': return self::it($num);
            case 'es': return self::es($num);
            case 'kk': return self::kk($num);
            case 'uz': return self::uz($num);
            case 'cz': return self::cz($num);
            case 'de': return self::de($num);
            case 'fa': return self::fa($num);
            case 'zh': return self::zh($num);
            case 'tr': return self::tr($num);
            default: throw new Exception("Unsupported language");
        }
    }

    // ================= RU =================
    public static function ru($num): string
    {
        $unit = ['рубль','рубля','рублей'];
        $kopeksForms = ['копейка','копейки','копеек'];

        $units = [
            ["ноль"],
            ["один", "одна"],
            ["два", "две"],
            ["три"], ["четыре"], ["пять"],
            ["шесть"], ["семь"], ["восемь"], ["девять"]
        ];

        $teens = [
            "десять","одиннадцать","двенадцать","тринадцать","четырнадцать",
            "пятнадцать","шестнадцать","семнадцать","восемнадцать","девятнадцать"
        ];

        $tens = [
            "", "десять","двадцать","тридцать","сорок",
            "пятьдесят","шестьдесят","семьдесят","восемьдесят","девяносто"
        ];

        $hundreds = [
            "", "сто","двести","триста","четыреста",
            "пятьсот","шестьсот","семьсот","восемьсот","девятьсот"
        ];

        $forms = [
            'thousand' => ["тысяча","тысячи","тысяч"],
            'million' => ["миллион","миллиона","миллионов"],
            'billion' => ["миллиард","миллиарда","миллиардов"]
        ];

        $getForm = function ($n, $forms) {
            $n = abs($n) % 100;
            if ($n >= 11 && $n <= 19) return $forms[2];
            $d = $n % 10;
            if ($d === 1) return $forms[0];
            if ($d >= 2 && $d <= 4) return $forms[1];
            return $forms[2];
        };

        $triplet = function ($num, $female) use ($units, $teens, $tens, $hundreds) {
            $words = [];
            $h = intdiv($num, 100);
            $t = intdiv($num % 100, 10);
            $u = $num % 10;

            if ($h > 0) $words[] = $hundreds[$h];

            if ($t > 1) {
                $words[] = $tens[$t];
                if ($u > 0) {
                    $words[] = $units[$u][($female && $u <= 2) ? 1 : 0] ?? $units[$u][0];
                }
            } elseif ($t === 1) {
                $words[] = $teens[$u];
            } else {
                if ($u > 0 || empty($words)) {
                    $words[] = $units[$u][($female && $u <= 2) ? 1 : 0] ?? $units[$u][0];
                }
            }

            return implode(" ", $words);
        };

        $negative = $num < 0;
        $num = abs($num);

        $rub = (int)floor($num);
        $kop = (int)round(($num - $rub) * 100);

        if ($kop === 100) {
            $rub++;
            $kop = 0;
        }

        $result = [];

        if ($rub === 0) {
            $result[] = "ноль";
        } else {
            $b = intdiv($rub, 1000000000);
            $m = intdiv($rub % 1000000000, 1000000);
            $t = intdiv($rub % 1000000, 1000);
            $r = $rub % 1000;

            if ($b > 0) {
                $result[] = $triplet($b, false);
                $result[] = $getForm($b, $forms['billion']);
            }
            if ($m > 0) {
                $result[] = $triplet($m, false);
                $result[] = $getForm($m, $forms['million']);
            }
            if ($t > 0) {
                $result[] = $triplet($t, true);
                $result[] = $getForm($t, $forms['thousand']);
            }
            if ($r > 0) {
                $result[] = $triplet($r, false);
            }
        }

        $text = implode(' ', $result);

        if ($negative) $text = "минус " . $text;

        return $text . ' ' .
            $getForm($rub, $unit) . ' ' .
            str_pad($kop, 2, '0', STR_PAD_LEFT) . ' ' .
            $getForm($kop, $kopeksForms);
    }

    // ================= EN =================
    public static function en($num): string
    {
        $units = ["zero","one","two","three","four","five","six","seven","eight","nine"];
        $teens = ["ten","eleven","twelve","thirteen","fourteen","fifteen","sixteen","seventeen","eighteen","nineteen"];
        $tens = ["","ten","twenty","thirty","forty","fifty","sixty","seventy","eighty","ninety"];

        $toWords = function ($n) use (&$toWords, $units, $teens, $tens) {
            if ($n < 10) return $units[$n];
            if ($n < 20) return $teens[$n - 10];
            if ($n < 100) return $tens[intdiv($n,10)] . ($n%10 ? ' '.$units[$n%10] : '');
            if ($n < 1000) return $units[intdiv($n,100)].' hundred'.($n%100?' '.$toWords($n%100):'');
            if ($n < 1000000) return $toWords(intdiv($n,1000)).' thousand'.($n%1000?' '.$toWords($n%1000):'');
            if ($n < 1000000000) return $toWords(intdiv($n,1000000)).' million'.($n%1000000?' '.$toWords($n%1000000):'');
            return $toWords(intdiv($n,1000000000)).' billion'.($n%1000000000?' '.$toWords($n%1000000000):'');
        };

        $int = (int)$num;
        $frac = (int)round(($num - $int) * 100);

        return $toWords($int) . " dollars " . str_pad($frac,2,'0',STR_PAD_LEFT) . " cents";
    }

    // ===== остальные языки (базово, без сложной морфологии) =====
    public static function hy($n){ return self::simple($n,"դրամ","լումա"); }
    public static function it($n){ return self::simple($n,"euro","centesimi"); }
    public static function es($n){ return self::simple($n,"euros","centavos"); }
    public static function kk($n){ return self::simple($n,"теңге","тиын"); }
    public static function uz($n){ return self::simple($n,"so‘m","tiyin"); }
    public static function cz($n){ return self::simple($n,"korun","haléřů"); }
    public static function de($n){ return self::simple($n,"euro","cent"); }
    public static function fa($n){ return self::simple($n,"ریال",""); }
    public static function zh($n){ return self::simple($n,"元","分"); }
    public static function tr($n){ return self::simple($n,"lira","kuruş"); }

    // упрощённая реализация
    private static function simple($num, $currency, $fraction): string
    {
        $int = (int)$num;
        $frac = (int)round(($num - $int) * 100);
        return $int . ' ' . $currency . ' ' . str_pad($frac,2,'0',STR_PAD_LEFT) . ' ' . $fraction;
    }
}