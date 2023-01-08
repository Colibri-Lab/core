<?php

/**
 * Минификация JS
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 * @version 1.0.0
 * 
 */

namespace Colibri\Common;

class Javascript
{

    /**
     * Удаляет из javascript-а все, что не есть код
     * главное помнить, что после каждой } внутри скоупа должно стоять ;, ну и после каждой строки тоже.
     * @param string $input
     * @return string
     * @testFunction testJavascriptShrink
     */
    public static function Shrink(string $input): string
    {

        return preg_replace_callback('(
                    (?:
                        (^|[-+\([{}=,:;!%^&*|?~]|/(?![/*])|return|throw) # context before regexp
                        (?:\s|//[^\n]*+\n|/\*(?:[^*]|\*(?!/))*+\*/)* # optional space
                        (/(?![/*])(?:
                            \\\\[^\n]
                            |[^[\n/\\\\]++
                            |\[(?:\\\\[^\n]|[^]])++
                        )+/) # regexp
                        |(^
                            |\'(?:\\\\.|[^\n\'\\\\])*\'
                            |"(?:\\\\.|[^\n"\\\\])*"
                            |([0-9A-Za-z_$]+)
                            |([-+]+)
                            |.
                        )
                    )(?:\s|//[^\n]*+\n|/\*(?:[^*]|\*(?!/))*+\*/)* # optional space
                )sx', function ($match) {
            static $last = '';
            $match += array_fill(1, 5, null); // avoid E_NOTICE
            list(, $context, $regexp, $result, $word, $operator) = $match;
            if ($word != '') {
                $l = ($last == 'return' ? " " : "");
                $result = ($last == 'word' ? "\n" : $l) . $result;
                $last = ($word == 'return' || $word == 'throw' || $word == 'break' ? 'return' : 'word');
            } elseif ($operator) {
                $result = ($last == $operator[0] ? "\n" : "") . $result;
                $last = $operator[0];
            } else {
                if ($regexp) {
                    $result = $context . ($context == '/' ? "\n" : "") . $regexp;
                }
                $last = '';
            }
            return $result;
        }, "$input\n");
    }
}