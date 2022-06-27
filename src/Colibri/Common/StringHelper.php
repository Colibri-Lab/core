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
use Colibri\Xml\XmlNode;

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
    public static function FromCamelCaseAttr(string $str, string $splitter = '-', bool $forceLowerCase = true): string
    {
        if (!is_string($str)) {
            return false;
        }
        return trim(preg_replace_callback('/([A-Z])/', function ($c) use ($splitter, $forceLowerCase) {
            return $splitter . ($forceLowerCase ? StringHelper::ToLower($c[1]) : $c[1]);
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

        $qs = StringHelper::ParseAsUrl($url);
        $qs->params = array_merge($qs->params ?: [], $params); 

        $query = [];
        foreach($qs->params as $key => $value) {
            $query[] = $key.'='.($encode ? urlencode($value) : $value);
        }

        return ($qs->scheme ? $qs->scheme.'://' : '').($qs->host ?? '').$qs->path.(!empty($query) ? '?'.implode('&', $query) : '');

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

    public static function UniqueWords(string $string, int $minlen = 3): array 
    {
        $string = StringHelper::StripHTML(trim($string));
        $a = preg_split("/ |,|\.|-|;|:|\(|\)|\{|\}|\[|\]/", $string);
        $a = array_unique($a);

        $b = array();
        foreach($a as $w) {
            if(StringHelper::Length($w) < $minlen){
                continue;
            }
            $b[] = StringHelper::ToLower($w);
        }

        return $b;
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

        return trim($hid, '-');
    }

    
    public static function AddNoIndex(string $text, bool $hard = true, string $domain = ''): string 
    {
            
        $text = str_replace('<a ', '<!--noindex--><a rel="nofollow" ', $text);
        $text = str_replace('</a>', '</a><!--/noindex-->', $text);
        
        if($hard) {
            try {
                $xml = XmlNode::LoadHTML('<'.'?xml version="1.0" encoding="utf-8" standalone="no"?'.'>'.$text, false);
                $hrefs = $xml->Query('//a');
                foreach($hrefs as $href) {
                    
                    $span = XMLNode::LoadNode('<span></span>', 'utf-8');
                    $attrs = $href->attributes;
                    foreach($attrs as $attr) {
                        $v = $attr->value;
                        $name = $attr->name;
                        $span->attributes->Append('data-'.$name, $v);
                    }
                    
                    $span->Append($href->nodes);
                    $href->ReplaceTo($span);
                }
                
                $text = $xml->xml;
                
            }
            catch(\Throwable $e) {

            }
            
            
        }

        return $text;
    }

    public static function StripHtmlAndBody(string $html): string 
    {
        $res = preg_match('/<body.*?>(.*)<\/body>/us', $html, $matches);
        if($res > 0) {
            return $matches[1];
        }
        return $html;
    }

    public static function RemoveEmoji(string $text): string 
    {
        return preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]/u', ' ', $text);
    }

    public static function ToObject(string $string, array $splitters = ['&', '=']): ExtendedObject
    {
        $return = new ExtendedObject(null, '', false);
        $parts = explode($splitters[0], $string);
        foreach($parts as $part) {
            $part = explode($splitters[1], $part);
            if(!$part[0]) {
                continue;
            }
            $return->{trim($part[0])} = trim($part[1]);
        }
        return $return;
    }

}
