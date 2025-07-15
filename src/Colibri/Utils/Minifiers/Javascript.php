<?php

/**
 * Minifiers
 *
 * @package Colibri\Utils\Minifiers
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 *
 */

namespace Colibri\Utils\Minifiers;

use Colibri\App;
use Colibri\Common\RandomizationHelper;
use Colibri\IO\FileSystem\File;

/**
 * Class for minifying JavaScript code.
 */
class Javascript
{
    private ?object $_config = null;

    /**
     * Minifies the given JavaScript content.
     *
     * @param string $content The JavaScript content to minify.
     * @return string The minified JavaScript content.
     */
    public static function Minify(string $content): string
    {
        $self = new self();
        return $self->Run($content);
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_config = App::$config->Query('minifier')->AsObject();
    }

    private function _getNames(array $range = null, string $prefix = ''): array
    {
        $names = [];
        foreach ($range as $letter1) {
            foreach ($range as $letter2) {
                foreach ($range as $letter3) {
                    $names[] = $prefix . $letter1 . $letter2 . $letter3;
                }
            }
        }
        return $names;
    }

    private function _getAdditionalObjectNames()
    {
        $return = [];
        foreach (App::$moduleManager->list as $module) {
            $p = $module->Config()->Query('config.paths.ui', [])->ToArray();
            if(!empty($p)) {
                foreach($p as $object) {
                    if(is_object($object)) {
                        $return[] = $object->root;
                    } elseif(is_array($object)) {
                        $return[] = $object['root'];
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Converts the JavaScript content.
     *
     * @param mixed $content The JavaScript content to convert.
     * @return string The converted JavaScript content.
     */
    private function _convert($content): string
    {
        $prefix = RandomizationHelper::Character(3);
        $names = $this->_getNames(range('A', 'Z'), $prefix);
        $roots = $this->_getAdditionalObjectNames();

        $content = '_c_ = () => {};' . $content;
        $content = preg_replace('/console\.log/s', '_c_', $content);
        $content = preg_replace('/console\.trace/s', '_c_', $content);
        $content = preg_replace('/console\.dir/s', '_c_', $content);
        $content = preg_replace('/console\.error/s', '_c_', $content);

        preg_match_all('/\n(Colibri|App'.(!empty($roots) ? '|'.implode('|', $roots) : '').')[^\[\(]*?\s\=\s/s', $content, $matches);
        $matches[0] = array_map(function ($v) {
            $v = trim(str_replace(' = ', '', $v), "\r\n\t ");
            if ($v === 'Colibri.UI.AddTemplate' || $v === 'Colibri.UI.Forms.Field.RegisterFieldComponent' || $v === 'Colibri.UI.Viewer.Register') {
                $v = 'Colibri.UI';
            }
            return $v;
        }, $matches[0]);
        $matches[0] = array_unique($matches[0]);
        rsort($matches[0]);
        $index = 0;
        $consts = [];
        array_map(function ($v) use ($names, &$consts, &$index, &$content) {
            $content = preg_replace_callback('/[^\'\"]' . $v . '/s', function ($match) use ($names, $index) { return substr($match[0], 0, 1) . $names[$index]; }, $content);
            $consts[$v] = $names[$index];
            $index++;
        }, $matches[0]);
        ksort($consts);
        $ret = [];
        foreach ($consts as $key => $value) {
            $ret[] = $key . ' = ' . $value;
        }
        $content = $content . implode(',', $ret);

        // надо заменить

        return $content;
    }

    /**
     * Minifies the JavaScript content.
     *
     * @param string $content The JavaScript content to minify.
     * @return string The minified JavaScript content.
     */
    public function Run(string $content): string
    {

        if ($this->_config?->convert ?? false) {
            $content = $this->_convert($content);
        }

        if ($this->_config?->type === 'uglify') {

            $commandline = $this->_config->command;
            $time = microtime(true);
            $runtime = App::$config->Query('runtime')->GetValue();
            $cacheFileIn = App::$appRoot . $runtime . 'code' . $time . '.js';
            $cacheFileOut = App::$appRoot . $runtime . 'code' . $time . '-minified.js';

            File::Write($cacheFileIn, $content);
            $commandline = sprintf($commandline, $cacheFileIn, $cacheFileOut);
            shell_exec($commandline);
            $content = File::Read($cacheFileOut);

            File::Delete($cacheFileIn);
            File::Delete($cacheFileOut);
        } elseif ($this->_config?->type === 'webpack') {
            $commandline = $this->_config->command;
            $time = microtime(true);
            $runtime = App::$config->Query('runtime')->GetValue();

            $cachePath = App::$appRoot . $runtime;
            $cacheFileIn = 'code' . $time . '.js';
            $cacheFileOut = 'code' . $time . '-minified.js';

            File::Write($cachePath . $cacheFileIn, $content);
            $commandline = sprintf($commandline, $cachePath . $cacheFileIn, $cachePath, $cacheFileOut);
            shell_exec($commandline);
            $content = File::Read($cachePath . $cacheFileOut);
            $content = substr($content, 6);
            $content = substr($content, 0, strlen($content) - 5) . ';';

            File::Delete($cachePath . $cacheFileIn);
            File::Delete($cachePath . $cacheFileOut);

        } elseif ($this->_config?->type === 'shrink') {
            return \JShrink\Minifier::minify($content, array('flaggedComments' => false));
        }

        return $content;

    }

}
