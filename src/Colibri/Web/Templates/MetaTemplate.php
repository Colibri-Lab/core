<?php

/**
 * Web
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Web
 *
 *
 */
namespace Colibri\Web\Templates;

use Colibri\Events\EventsContainer;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\ExtendedObject;

class MetaTemplate extends Template
{

    const JS = 'js';
    const PHP = 'php';

    /**
     * Конструктор
     *
     * @param string $file файл шаблона
     */
    public function __construct(string $file)
    {
        parent::__construct($file . '.meta');
    }

    /**
     * Вывод шаблона
     *
     * @param string $mode тип конвертации php/js
     * @param mixed $args
     * @return string
     */
    public function Render(mixed $args = null): string
    {

        $args = (object)$args;

        $this->DispatchEvent(EventsContainer::TemplateRendering, (object)['template' => $this, 'args' => $args]);

        $content = File::Read($this->_file);
        if ($args->mode === 'js') {
            $content = '
                ((container) => {
                    const mode = \'' . $args->mode . '\';
                    let args = ' . json_encode($args, \JSON_UNESCAPED_UNICODE) . ';
                    ' . ($args->reload ? 'const reloadHandler = eval(args.reload); reloadHandler(container, args);' : '') . '
                    ' . $this->_convertToJS($content) . '
                    return args;
                })(container);
            ';
        }
        else if ($args->mode === 'php') {
            $f = eval('return function($mode, $args) {
                ' . $this->_convertToPhp($content) . '
                return $args;
            };');
            // возварщает обьект
            $content = $f($args->mode, $args);
        }
        else {
            $content = '';
        }

        $this->DispatchEvent(EventsContainer::TemplateRendered, (object)['template' => $this, 'content' => $content]);

        return $content;

    }

    /**
     * Конвертирует мета-язык в Javascript
     * @param string $code
     * @return string
     */
    private function _convertToJS(string $code): string
    {
        $code = \preg_replace_callback('/\{\{([^\}\}]+)\}\}/s', function ($match) {
            return 'args.' . $match[1];
        }, $code);
        return $code;
    }

    /**
     * Конвертирует мета-язык в Php
     * @param string $code
     * @return string
     */
    private function _convertToPhp(string $code): string
    {
        $code = \preg_replace_callback('/\{\{([^\}\}]+)\}\}/s', function ($match) {
            return '$args->' . $match[1];
        }, $code);
        return $code;
    }

    /**
     * Замена вставок в шаблон
     *
     * @param string $code код для выполнения
     * @param ExtendedObject $args аргументы для передачи в код
     * @return string
     */
    public function RenderCode(string $code, mixed $args): string
    {
        return $code;
    }

}
