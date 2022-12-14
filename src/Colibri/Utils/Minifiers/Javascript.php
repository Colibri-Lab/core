<?php

namespace Colibri\Utils\Minifiers;
use Colibri\App;
use Colibri\Common\RandomizationHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;


class Javascript 
{

    private ?object $_config = null;

    public static function Minify(string $content): string
    {
        $self = new self();
        return $self->Run($content);
    }

    public function __construct()
    {
        $this->_config = App::$config->Query('minifier')->AsObject();
    }

    private function _getNames(array $range = null, string $prefix = ''): array
    {
        $names = [];
        foreach($range as $letter1) {
            foreach($range as $letter2) {
                foreach($range as $letter3) {
                    $names[] = $prefix . $letter1 . $letter2 . $letter3;
                }
            }
        }
        return $names;
    }

    private function _convert($content): string 
    {
        $prefix = RandomizationHelper::Character(1);
        $names = $this->_getNames(range('A', 'Z'), $prefix);

        $content = '_c_ = () => {};' . $content;
        $content = preg_replace('/console\.log\([^\)]*\)/s', '_c_()', $content);
        $content = preg_replace('/console\.dir\([^\)]*\)/s', '_c_()', $content);
        $content = preg_replace('/console\.error\([^\)]*\)/s', '_c_()', $content);

        preg_match_all('/\n(Colibri|App)[^\[\(]*?\s\=\s/s', $content, $matches);
        $matches[0] = array_map(function ($v) {
            $v = trim(str_replace(' = ', '', $v), "\r\n\t ");
            if($v === 'Colibri.UI.AddTemplate' || $v === 'Colibri.UI.Forms.Field.RegisterFieldComponent' || $v === 'Colibri.UI.Viewer.Register') {
                $v = 'Colibri.UI';
            }
            return $v; 
        }, $matches[0]);
        $matches[0] = array_unique($matches[0]);
        rsort($matches[0]);
        $index = 0;
        $consts = [];
        array_map(function ($v) use ($names, &$consts, &$index, &$content) {
            $content = preg_replace_callback('/[^\'\"]' . $v . '/s', function ($match) use ($names, $index) {
                return substr($match[0], 0, 1) . $names[$index];
            }, $content);
            $consts[$v] = $names[$index];
            $index++;
        }, $matches[0]);
        ksort($consts);
        $ret = [];
        foreach($consts as $key => $value) {
            $ret[] = $key . ' = ' . $value;
        }
        return $content . implode(',', $ret);
    }

    public function Run(string $content): string
    {

        if($this->_config->convert) {
            $content = $this->_convert($content);
        }

        if($this->_config?->type === 'uglify') {

            $commandline = $this->_config->command;
            $time = microtime(true);
            $runtime = App::$config->Query('runtime')->GetValue();
            $cacheFileIn = App::$appRoot . $runtime . 'code'.$time.'.js';
            $cacheFileOut = App::$appRoot . $runtime . 'code'.$time.'-minified.js';
            
            File::Write($cacheFileIn, $content);
            $commandline = sprintf($commandline, $cacheFileIn, $cacheFileOut);
            shell_exec($commandline);
            $content = File::Read($cacheFileOut);

            File::Delete($cacheFileIn);
            File::Delete($cacheFileOut);

        } elseif ($this->_config?->type === 'shrink') {
            return \JShrink\Minifier::minify($content, array('flaggedComments' => false));
        }

        return $content;

    }

}