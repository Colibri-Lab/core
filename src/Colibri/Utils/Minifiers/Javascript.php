<?php

namespace Colibri\Utils\Minifiers;
use Colibri\App;
use Colibri\IO\FileSystem\File;


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

    public function Run(string $content): string
    {
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