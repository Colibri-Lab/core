<?php

/**
 * Utils
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils
 * 
 */

namespace Colibri\Utils;

use Colibri\App;

/**
 * Методы для вывода отладочной информации
 */
class Debug
{

    /**
     * Превращает аргументы в правильный текстовый вид
     *
     * @param array $args
     * @return array
     */
    private static function _createArgs($args)
    {
        $count = count($args);
        $result = array();
        for ($i = 0; $i < $count; $i++) {
            switch (gettype($args[$i])) {
                case "boolean":
                    $result[] = $args[$i] ? 'true' : 'false';
                    break;
                case "NULL":
                    $result[] = "NULL";
                    break;
                default:
                    $result[] = print_r($args[$i], true);
            }
        }
        return $result;
    }

    /**
     * Функция вывода
     *
     * @return void
     */
    public static function Out()
    {
        $result = self::_createArgs(func_get_args());
        if (App::$request->server->{'commandline'}) {
            echo str_replace("<", "&lt;", str_replace(">", "&gt;", implode(" : ", $result))) . "\n";
            // закрываем ob
            try { @ob_flush(); } catch(\Throwable $e) {  }
        } else {
            echo "<pre>\n" . str_replace("<", "&lt;", str_replace(">", "&gt;", implode(" : ", $result))) . "\n</pre>";
        }
    }

    /**
     * Функция вывода
     *
     * @return string
     */
    public static function ROut()
    {
        $result = self::_createArgs(func_get_args());
        return "\n" . str_replace("<", "&lt;", str_replace(">", "&gt;", implode(" : ", $result))) . "\n";
    }

    /**
     * Печатает красиво обьект в виде дерева
     *
     */
    public static function IOut()
    {
        $clickevent = 'onclick="event.currentTarget.parentElement.nextElementSibling && (event.currentTarget.parentElement.nextElementSibling.style.display = event.currentTarget.parentElement.nextElementSibling.style.display == \'\' ? \'none\' : \'\');"';
        $result = self::_createArgs(func_get_args());
        $result = print_r($result, true);
        $result = str_replace("<", "&lt;", str_replace(">", "&gt;", $result));
        $result = preg_replace("/\s*?\[(.*)\] \=&gt; (.*?)\n/mi", "\n<div class='legend' " . $clickevent . ">[\$1] => \$2</div>\n", $result);
        $result = preg_replace("/(<div class='legend' " . preg_quote($clickevent) . ">.*<\/div>)\n\s*?\(/mi", "\n<div class='object'><div class='hilite'>\$1</div><div class='children' style='display: none'>\n", $result);
        $result = preg_replace("/\n\s*?\)\n/", "\n</div></div>\n", $result);
        $result = preg_replace("/Array\n\(\n/i", "\n<div class='result'><div class='object'><div class='legend' " . $clickevent . ">IOUT - Result</div><div class='children'>\n", $result) . '</div>';
        echo $result . '<style type="text/css">div.result { border: 1px solid #f2f2f2; padding: 10px;} div.legend { cursor: pointer; padding: 2px 0; } div.object { font: 12px monospace; } div.children { padding: 1px 0 1px 50px; border-left: 1px solid #f9f9f9; min-height: 5px; } div.hilite { color: #050; }</style>';
    }
}