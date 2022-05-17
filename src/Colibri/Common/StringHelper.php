<?php

/**
 * Строковые функции
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 * @version 1.0.0
 * 
 */

namespace Colibri\Common;

use Colibri\Collections\Collection;
use Colibri\Common\RandomizationHelper;
use Colibri\Utils\Debug;
use Colibri\Utils\ExtendedObject;

class StringHelper
{

    /**
     * В прописные
     * 
     * @param string $s
     * @return string
     * @testFunction testStringHelperToLower
     */
    public static function ToLower(string $s): string
    {
        return mb_strtolower($s, "UTF-8");
    }

    /**
     * В заглавные
     *
     * @param string $s
     * @return string
     * @testFunction testStringHelperToUpper
     */
    public static function ToUpper(string $s): string
    {
        return mb_strtoupper($s, "UTF-8");
    }

    /**
     * Проверяет не состоит ли текст только из заглавных букв
     * @param string $s строка
     * @return bool 
     * @testFunction testStringHelperIsUpper
     */
    public static function IsUpper(string $s): bool
    {
        if (!is_string($s)) {
            return false;
        }
        return $s == StringHelper::ToUpper($s);
    }

    /**
     * Проверяет не состоит ли текст только из прописных букв
     * @param string $s строка
     * @return bool 
     * @testFunction testStringHelperIsLower
     */
    public static function IsLower(string $s): bool
    {
        if (!is_string($s)) {
            return false;
        }
        return $s == StringHelper::ToLower($s);
    }

    /**
     * Первая заглавная осталвные прописные
     *
     * @param string $str
     * @return string
     * @testFunction testStringHelperToUpperFirst
     */
    public static function ToUpperFirst(string $str): string
    {
        if (!is_string($str)) {
            return false;
        }
        return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8')).mb_substr($str, 1);
    }

    public static function Replace(string|array $subject, string|array $search, string|array $replace, int &$count = 0): string|array |bool
    {
        if (!is_array($search) && is_array($replace)) {
            return false;
        }
        if (is_array($subject)) {
            // call mb_replace for each single string in $subject
            foreach ($subject as &$string) {
                $string = & self::Replace($search, $replace, $string, $c);
                $count += $c;
            }
            unset($string);
        }
        elseif (is_array($search)) {
            if (!is_array($replace)) {
                foreach ($search as &$string) {
                    $subject = self::Replace($string, $replace, $subject, $c);
                    $count += $c;
                }
            }
            else {
                $n = max(count($search), count($replace));
                while ($n--) {
                    $subject = self::Replace(current($search), current($replace), $subject, $c);
                    $count += $c;
                    next($search);
                    next($replace);
                }
            }
        }
        else {
            $parts = mb_split(preg_quote($search), $subject);
            $count = count($parts) - 1;
            $subject = implode($replace, $parts);
        }
        return $subject;
    }

    /**
     * Превратить строки из прописных с тире в кэмелкейс
     *
     * @param string $str
     * @param boolean $firstCapital
     * @return string
     * @testFunction testStringHelperToCamelCaseAttr
     */
    public static function ToCamelCaseAttr(string $str, bool $firstCapital = false, string $splitter = '\-'): string
    {
        if (!is_string($str)) {
            return false;
        }

        if ($firstCapital) {
            $str = StringHelper::ToUpperFirst($str);
        }

        return preg_replace_callback('/' . $splitter . '([A-Za-z1-9])/', function ($c) {
            return StringHelper::ToUpper(substr($c[1], 0, 1)) . StringHelper::ToLower(substr($c[1], 1));
        }, $str);
    }

    /**
     * Из кэмел кейса в прописные с тирешками, для использования в качестве названий аттрибутов
     *
     * @param string $str
     * @return string
     * @testFunction testStringHelperFromCamelCaseAttr
     */
    public static function FromCamelCaseAttr(string $str, string $splitter = '-'): string
    {
        if (!is_string($str)) {
            return false;
        }
        return trim(preg_replace_callback('/([A-Z])/', function ($c) use ($splitter) {
            return $splitter . StringHelper::ToLower($c[1]);
        }, $str), $splitter);
    }

    /**
     * Из under_score в camelcase
     *
     * @param string $str
     * @param boolean $firstCapital
     * @return string
     * @testFunction testStringHelperToCamelCaseVar
     */
    public static function ToCamelCaseVar(string $str, bool $firstCapital = false): string
    {
        if (!is_string($str)) {
            return false;
        }
        if ($firstCapital) {
            $str = StringHelper::ToUpperFirst($str);
        }

        return preg_replace_callback('/_([A-Za-z1-9])/', function ($c) {
            return StringHelper::ToUpperFirst($c[1]);
        }, $str);
    }

    /**
     * Из CamelCase в under_score
     *
     * @param string $str
     * @return string
     * @testFunction testStringHelperFromCamelCaseVar
     */
    public static function FromCamelCaseVar(string $str): string
    {
        if (!is_string($str)) {
            return false;
        }
        return trim(preg_replace_callback('/([A-Z])/', function ($c) {
            return '_' . StringHelper::ToLower($c[1]);
        }, $str), '_');
    }

    /**
     * Проверяет на валидность электронного адреса
     *
     * @param string $address
     * @return boolean
     * @testFunction testStringHelperIsEmail
     */
    public static function IsEmail(string $address): bool
    {
        if (!is_string($address)) {
            return false;
        }

        if (function_exists('filter_var')) {
            return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
        }
        else {
            return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
        }
    }

    /**
     * Проверяет на валидность URL адреса
     *
     * @param string $address
     * @return boolean
     * @testFunction testStringHelperIsUrl
     */
    public static function IsUrl(string $address): bool
    {
        if (function_exists('filter_var')) {
            return filter_var($address, FILTER_VALIDATE_URL) !== false;
        }
        else {
            return strstr($address, 'http://') !== false || strstr($address, 'https://') !== false || substr($address, 'ftp://') !== false || substr($address, '//') === 0;
        }
    }

    /**
     * Проверяет не заканчивается ли строка на заданную
     *
     * @param string $string
     * @param string $end
     * @return boolean
     * @testFunction testStringHelperEndsWith
     */
    public static function EndsWith(string $string, string $end): bool
    {
        return substr($string, strlen($string) - strlen($end)) == $end;
    }

    /**
     * Проверяет не налинается ли строка на заданную
     *
     * @param string $string
     * @param string $start
     * @return boolean
     * @testFunction testStringHelperStartsWith
     */
    public static function StartsWith(string $string, string $start): bool
    {
        return substr($string, 0, strlen($start)) == $start;
    }

    /**
     * Превращает url в виде hyphen-text в CamelCase Namespace
     *
     * @param string $url
     * @return string
     * @testFunction testStringHelperUrlToNamespace
     */
    public static function UrlToNamespace(string $url): string
    {
        if (!is_string($url)) {
            return false;
        }

        $class = explode('/', trim($url, '/'));
        $className = [];
        foreach ($class as $name) {
            $className[] = StringHelper::ToCamelCaseAttr($name, true);
        }
        return implode('\\', $className);
    }

    /**
     * Добавляет или удаляет часть параметров в queryString
     * @param string $url URL
     * @param mixed $params 
     * @param bool $encode 
     * @return string 
     * @testFunction testStringHelperAddToQueryString
     */
    public static function AddToQueryString(string $url, string|array |object $params, bool $encode = true): string
    {
        if (!is_string($url) || !(is_object($params) || is_array($params))) {
            return false;
        }

        if (strstr($url, '?') !== false) {
            $qs = explode('?', $url);
            $hashTable = Collection::FromString($qs[1], ['=', '&']);
            $url = $qs[0];
        }
        else {
            $hashTable = new Collection();
        }

        $hashTable->Append($params);

        return $url . ($hashTable->Count() > 0 ? '?' : '') . $hashTable->ToString(['=', '&'], function ($k, $v) use ($encode) {
            return $encode ? urlencode($v) : $v;
        });
    }

    /**
     * Возвращает произвольную строку заданной длины
     *
     * @param int $length
     * @return string
     * @testFunction testStringHelperRandomize
     */
    public static function Randomize(int $length): string
    {
        return RandomizationHelper::Mixed($length);
    }

    /**
     * Подгатавливает текст для вложения в html аттрибут
     *
     * @param string $string
     * @param boolean $quoters
     * @return string
     * @testFunction testStringHelperPrepareAttribute
     */
    public static function PrepareAttribute(string $string, bool $quoters = false): string
    {
        if ($quoters) {
            $string = preg_replace("/\'/", "&rsquo;", $string);
        }
        $string = preg_replace("/&amp;/", "&", $string);
        $string = preg_replace("/&nbsp;/", " ", $string);
        $string = preg_replace("/&/", "&amp;", $string);
        $string = preg_replace("/\n/", '', $string);
        return preg_replace("/\"/", "&quot;", $string);
    }

    /**
     * Unescape-ид строку
     *
     * @param string $s
     * @return string
     * @testFunction testStringHelperUnescape
     */
    public static function Unescape(string $s): string
    {
        return preg_replace_callback(
            '/% (?: u([A-F0-9]{1,4}) | ([A-F0-9]{1,2})) /sxi',
            function ($p) {
            $c = '';
            if ($p[1]) {
                $u = pack('n', hexdec($p[1]));
                $c = @iconv('UCS-2BE', 'windows-1251', $u);
            }
            return $c;
        },
            $s
        );
    }

    /**
     * Удаляет разметку из строки
     *
     * @param string $html
     * @return string
     * @testFunction testStringHelperStripHTML
     */
    public static function StripHTML(string $html, ?string $allowedTags = null): string
    {
        return strip_tags($html, $allowedTags);
    }

    /**
     * Вырезает кусок из строки
     *
     * @param string $string
     * @param int $start
     * @param int $length
     * @return string
     * @testFunction testStringHelperSubstring
     */
    public static function Substring(string $string, int $start, ?int $length = null): string
    {
        if (!is_string($string) || !is_numeric($start)) {
            return false;
        }

        $enc = mb_detect_encoding($string);
        if (!$length) {
            $length = mb_strlen($string, $enc);
        }
        return mb_substr($string, $start, $length, $enc);
    }

    /**
     * Возвращает длину строки
     *
     * @param string $string
     * @return int
     * @testFunction testStringHelperLength
     */
    public static function Length(string $string): int
    {
        $encoding = mb_detect_encoding($string);
        if (!$encoding) {
            $encoding = 'utf-8';
        }
        return mb_strlen($string, $encoding);
    }

    /**
     * Форматирует число в виде строки
     * @param int|float $secuence число, которое нужно форматировать
     * @param array $labels слова определяющие 
     * @param bool $viewnumber показать число перед словом
     * @return string 
     * @testFunction testStringHelperFormatSequence
     */
    public static function FormatSequence(float $secuence, array $labels = array("год", "года", "лет"), bool $viewnumber = false): string
    {
        $isfloat = intval($secuence) != floatval($secuence);
        $floatPoint = floatval($secuence) - intval($secuence);
        $floatPoint = $floatPoint . '';
        $floatPoint = str_replace('0.', '', $floatPoint);
        $floatLength = strlen($floatPoint);

        $s = "";
        if ($viewnumber) {
            $s = $secuence . " ";
        }
        $ssecuence = strval($secuence);
        $sIntervalLastChar = substr($ssecuence, strlen($ssecuence) - 1, 1);
        if ((int)$secuence > 10 && (int)$secuence < 20) {
            return $s . $labels[2]; //"лет"
        }
        else {
            if (!$isfloat || $floatLength > 1) {
                switch (intval($sIntervalLastChar)) {
                    case 1:
                        return $s . $labels[0];
                    case 2:
                    case 3:
                    case 4:
                        return $s . $labels[1];
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    case 0:
                        return $s . $labels[2];
                    default: {
                            break;
                        }
                }
            }
            else {
                switch (intval($sIntervalLastChar)) {
                    case 1:
                        return $s . $labels[0];
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                        return $s . $labels[1];
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    case 0:
                        return $s . $labels[2];
                    default: {
                            break;
                        }
                }
            }
        }
        return '';
    }

    /**
     * Размер файла текстом
     * @param int $number размер
     * @param int $range делитель
     * @param array $postfixes слова
     * @return string результат
     * @testFunction testStringHelperFormatFileSize
     */
    public static function FormatFileSize(int $number, int $range = 1024, array $postfixes = array("bytes", "Kb", "Mb", "Gb", "Tb")): string
    {
        for ($j = 0; $j < count($postfixes); $j++) {
            if ($number <= $range) {
                break;
            }
            else {
                $number = $number / $range;
            }
        }
        $number = round($number, 2);
        return $number . " " . $postfixes[$j];
    }

    /**
     * Делит по количеству букв и добавляет ...
     *
     * @param string $str
     * @param int $length
     * @param string $ellipsis
     * @return string|null
     * @testFunction testStringHelperTrimLength
     */
    public static function TrimLength(string $str, int $length, string $ellipsis = "..."): ?string
    {
        if (!is_numeric($length)) {
            return null;
        }
        return StringHelper::Substring($str, 0, $length - 3) . $ellipsis;
    }

    /**
     * Вырезает нужное количество слов из текста
     *
     * @param string $text
     * @param int $n
     * @param string $ellipsis
     * @return string
     * @testFunction testStringHelperWords
     */
    public static function Words(string $text, int $n, string $ellipsis = "..."): string
    {
        $text = StringHelper::StripHTML(trim($text));
        $a = preg_split("/ |,|\.|-|;|:|\(|\)|\{|\}|\[|\]/", $text);

        if (!empty($a)) {
            if (count($a) < $n) {
                return $text;
            }

            $l = 0;
            for ($j = 0; $j < $n; $j++) {
                $l = $l + mb_strlen($a[$j]) + 1;
            }

            return StringHelper::Substring(trim($text), 0, $l) . $ellipsis;
        }
        else {
            return StringHelper::Substring(trim($text), 0, $n);
        }
    }

    /**
     * Добавляет перед текстом нужное количество указанных букв
     * например если вызврать StringHelper::Expand('1', 4, '0') - получим 0001
     *
     * @param string $s текст
     * @param int $l количество
     * @param string $c символ
     * @return string
     * @testFunction testStringHelperExpand
     */
    public static function Expand(string $s, int $l, string $c): string
    {
        if (strlen($s) >= $l) {
            return $s;
        }
        else {
            return str_repeat($c, $l - strlen($s)) . $s;
        }
    }

    /**
     * Создает новый GUID
     * @return string 
     * @testFunction testStringHelperGUID
     */
    public static function GUID(): string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    /**
     * Делит строку на куски по разделителям
     * @param string $string строка на разделение
     * @param string[]|string $delimiters разделители
     * @param bool $addDelimiters включить разделители в массив
     * @return string[]|null
     * @testFunction testStringHelperExplode
     */
    public static function Explode(string $string, string|array $delimiters, bool $addDelimiters = false): ?array
    {
        if (!is_array(($delimiters)) && !is_array($string)) {
            $return = preg_split('/' . preg_quote($delimiters) . '/u', $string);
            if ($addDelimiters) {
                $ret = [];
                foreach ($return as $r) {
                    $ret[] = $r;
                    $ret[] = $delimiters;
                }
                unset($ret[count($ret) - 1]);
                $return = $ret;
            }
            return $return;
        }
        else if (!is_array($delimiters) && is_array($string)) {
            $items = [];
            foreach ($string as $item) {
                $r = self::Explode($item, $delimiters, $addDelimiters);
                foreach ($r as $sub_item) {
                    $items[] = $sub_item;
                }
            }
            return $items;
        }
        else if (is_array($delimiters) && !is_array($string)) {
            $string_array = [$string];
            foreach ($delimiters as $delimiter) {
                $string_array = self::Explode($string_array, $delimiter, $addDelimiters);
            }
            return $string_array;
        }
        return null;
    }

    /**
     * Соединяет строку и разделитель
     * @param string[] $array массив
     * @param string $splitter разделитель
     * @return string
     * @testFunction testStringHelperImplode
     */
    public static function Implode(array $array, string $splitter): string 
    {
        if (!is_array($array) || !is_string($splitter)) {
            return false;
        }
        return implode($splitter, $array);
    }

    /**
     * Возвращает распаршенный url
     * @param string $url 
     * @return ExtendedObject
     */
    public static function ParseAsUrl(string $url): ExtendedObject
    {
        $res = (object)parse_url($url);
        if (isset($res->query)) {
            $params = array();
            $par = explode('&', $res->query);
            foreach ($par as $param) {
                if (!VariableHelper::IsEmpty(trim($param))) {
                    $p = explode('=', $param);
                    $params[$p[0]] = isset($p[1]) ? $p[1] : '';
                }
            }
            $res->params = $params;
        }

        if (isset($res->path)) {
            $pathInfo = pathinfo($res->path);
            $res->location = (object)$pathInfo;
        }

        return new ExtendedObject($res);
    }

    public static function Transliterate(string $string): string
    {
        $string = mb_ereg_replace("ый", "yj", $string);
        $string = mb_ereg_replace("а", "a", $string);
        $string = mb_ereg_replace("б", "b", $string);
        $string = mb_ereg_replace("в", "v", $string);
        $string = mb_ereg_replace("г", "g", $string);
        $string = mb_ereg_replace("д", "d", $string);
        $string = mb_ereg_replace("е", "e", $string);
        $string = mb_ereg_replace("ё", "yo", $string);
        $string = mb_ereg_replace("ж", "zh", $string);
        $string = mb_ereg_replace("з", "z", $string);
        $string = mb_ereg_replace("и", "i", $string);
        $string = mb_ereg_replace("й", "y", $string);
        $string = mb_ereg_replace("к", "k", $string);
        $string = mb_ereg_replace("л", "l", $string);
        $string = mb_ereg_replace("м", "m", $string);
        $string = mb_ereg_replace("н", "n", $string);
        $string = mb_ereg_replace("о", "o", $string);
        $string = mb_ereg_replace("п", "p", $string);
        $string = mb_ereg_replace("р", "r", $string);
        $string = mb_ereg_replace("с", "s", $string);
        $string = mb_ereg_replace("т", "t", $string);
        $string = mb_ereg_replace("у", "u", $string);
        $string = mb_ereg_replace("ф", "f", $string);
        $string = mb_ereg_replace("х", "h", $string);
        $string = mb_ereg_replace("ц", "c", $string);
        $string = mb_ereg_replace("ч", "ch", $string);
        $string = mb_ereg_replace("ш", "sh", $string);
        $string = mb_ereg_replace("щ", "sch", $string);
        $string = mb_ereg_replace("ъ", "j", $string);
        $string = mb_ereg_replace("ы", "y", $string);
        $string = mb_ereg_replace("ь", "", $string);
        $string = mb_ereg_replace("э", "e", $string);
        $string = mb_ereg_replace("ю", "yu", $string);
        $string = mb_ereg_replace("я", "ya", $string);

        $string = mb_ereg_replace("ЫЙ", "YJ", $string);
        $string = mb_ereg_replace("ыЙ", "yJ", $string);
        $string = mb_ereg_replace("Ый", "Yj", $string);
        $string = mb_ereg_replace("А", "A", $string);
        $string = mb_ereg_replace("Б", "B", $string);
        $string = mb_ereg_replace("В", "V", $string);
        $string = mb_ereg_replace("Г", "G", $string);
        $string = mb_ereg_replace("Д", "D", $string);
        $string = mb_ereg_replace("Е", "E", $string);
        $string = mb_ereg_replace("Ё", "Yo", $string);
        $string = mb_ereg_replace("Ж", "Zh", $string);
        $string = mb_ereg_replace("З", "Z", $string);
        $string = mb_ereg_replace("И", "I", $string);
        $string = mb_ereg_replace("Й", "Y", $string);
        $string = mb_ereg_replace("К", "K", $string);
        $string = mb_ereg_replace("Л", "L", $string);
        $string = mb_ereg_replace("М", "M", $string);
        $string = mb_ereg_replace("Н", "N", $string);
        $string = mb_ereg_replace("О", "O", $string);
        $string = mb_ereg_replace("П", "P", $string);
        $string = mb_ereg_replace("Р", "R", $string);
        $string = mb_ereg_replace("С", "S", $string);
        $string = mb_ereg_replace("Т", "T", $string);
        $string = mb_ereg_replace("У", "U", $string);
        $string = mb_ereg_replace("Ф", "F", $string);
        $string = mb_ereg_replace("Х", "H", $string);
        $string = mb_ereg_replace("Ц", "C", $string);
        $string = mb_ereg_replace("Ч", "Ch", $string);
        $string = mb_ereg_replace("Ш", "Sh", $string);
        $string = mb_ereg_replace("Щ", "Sch", $string);
        $string = mb_ereg_replace("Ъ", "J", $string);
        $string = mb_ereg_replace("Ы", "Y", $string);
        $string = mb_ereg_replace("Ь", "", $string);
        $string = mb_ereg_replace("Э", "E", $string);
        $string = mb_ereg_replace("Ю", "Yu", $string);
        return mb_ereg_replace("Я", "Ya", $string);
    }

    public static function TransliterateBack(string $string): string
    {
        $string = mb_ereg_replace("yj", "ый", $string);
        $string = mb_ereg_replace("a", "а", $string);
        $string = mb_ereg_replace("b", "б", $string);
        $string = mb_ereg_replace("v", "в", $string);
        $string = mb_ereg_replace("g", "г", $string);
        $string = mb_ereg_replace("d", "д", $string);
        $string = mb_ereg_replace("e", "е", $string);
        $string = mb_ereg_replace("yo", "ё", $string);
        $string = mb_ereg_replace("zh", "ж", $string);
        $string = mb_ereg_replace("z", "з", $string);
        $string = mb_ereg_replace("i", "и", $string);
        $string = mb_ereg_replace("y", "й", $string);
        $string = mb_ereg_replace("k", "к", $string);
        $string = mb_ereg_replace("l", "л", $string);
        $string = mb_ereg_replace("m", "м", $string);
        $string = mb_ereg_replace("n", "н", $string);
        $string = mb_ereg_replace("o", "о", $string);
        $string = mb_ereg_replace("p", "п", $string);
        $string = mb_ereg_replace("r", "р", $string);
        $string = mb_ereg_replace("s", "с", $string);
        $string = mb_ereg_replace("t", "т", $string);
        $string = mb_ereg_replace("u", "у", $string);
        $string = mb_ereg_replace("f", "ф", $string);
        $string = mb_ereg_replace("h", "х", $string);
        $string = mb_ereg_replace("c", "ц", $string);
        $string = mb_ereg_replace("ch", "ч", $string);
        $string = mb_ereg_replace("sh", "ш", $string);
        $string = mb_ereg_replace("sch", "щ", $string);
        $string = mb_ereg_replace("j", "ъ", $string);
        $string = mb_ereg_replace("y", "ы", $string);
        $string = mb_ereg_replace("e", "э", $string);
        $string = mb_ereg_replace("yu", "ю", $string);
        $string = mb_ereg_replace("ya", "я", $string);
        $string = mb_ereg_replace("YJ", "ЫЙ", $string);
        $string = mb_ereg_replace("yJ", "ыЙ", $string);
        $string = mb_ereg_replace("Yj", "Ый", $string);
        $string = mb_ereg_replace("A", "А", $string);
        $string = mb_ereg_replace("B", "Б", $string);
        $string = mb_ereg_replace("V", "В", $string);
        $string = mb_ereg_replace("G", "Г", $string);
        $string = mb_ereg_replace("D", "Д", $string);
        $string = mb_ereg_replace("E", "Е", $string);
        $string = mb_ereg_replace("Yo", "Ё", $string);
        $string = mb_ereg_replace("Zh", "Ж", $string);
        $string = mb_ereg_replace("Z", "З", $string);
        $string = mb_ereg_replace("I", "И", $string);
        $string = mb_ereg_replace("Y", "Й", $string);
        $string = mb_ereg_replace("K", "К", $string);
        $string = mb_ereg_replace("L", "Л", $string);
        $string = mb_ereg_replace("M", "М", $string);
        $string = mb_ereg_replace("N", "Н", $string);
        $string = mb_ereg_replace("O", "О", $string);
        $string = mb_ereg_replace("P", "П", $string);
        $string = mb_ereg_replace("R", "Р", $string);
        $string = mb_ereg_replace("S", "С", $string);
        $string = mb_ereg_replace("T", "Т", $string);
        $string = mb_ereg_replace("U", "У", $string);
        $string = mb_ereg_replace("F", "Ф", $string);
        $string = mb_ereg_replace("H", "Х", $string);
        $string = mb_ereg_replace("C", "Ц", $string);
        $string = mb_ereg_replace("Ch", "Ч", $string);
        $string = mb_ereg_replace("Sh", "Ш", $string);
        $string = mb_ereg_replace("Sch", "Щ", $string);
        $string = mb_ereg_replace("J", "Ъ", $string);
        $string = mb_ereg_replace("Y", "Ы", $string);
        $string = mb_ereg_replace("E", "Э", $string);
        $string = mb_ereg_replace("Yu", "Ю", $string);
        return mb_ereg_replace("Ya", "Я", $string);
    }

    public static function CreateHID(string $text, bool $trans = true): string
    {

        if ($trans) {
            $hid = preg_replace('/\-+/', '-', substr(preg_replace(
                '/[^\w]/i',
                '-',
                str_replace(
                '«',
                '',
                str_replace(
                '»',
                '',
                strtolower(StringHelper::Transliterate(trim($text, "\n\r ")))
            )
            )
            ), 0, 200));
        }
        else {
            $hid = iconv('cp1251', 'UTF-8', preg_replace('/\-+/', '-', substr(preg_replace(
                '/[^\w\x7F-\xFF]/i',
                '-',
                str_replace(
                '«',
                '',
                str_replace(
                '»',
                '',
                strtolower(trim(iconv('UTF-8', 'cp1251', $text), "\n\r "))
            )
            )
            ), 0, 200)));
        }

        return $hid;
    }
}
