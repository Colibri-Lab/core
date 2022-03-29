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

use Colibri\App;
use Colibri\Events\EventsContainer;
use Colibri\AppException;
use Colibri\Events\TEventDispatcher;
use Colibri\IO\FileSystem\Directory;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Utils\ExtendedObject;

class PhpTemplate extends Template
{

    /**
     * Конструктор
     *
     * @param string $file файл шаблона
     */
    public function __construct($file)
    {
        parent::__construct($file . '.php');
    }


    /**
     * Вывод шаблона
     *
     * @param mixed $args
     * @return string
     */
    public function Render($args = null)
    {

        if (!File::Exists($this->_file)) {
            throw new AppException('Unknown template, realpath: ' . $this->_file);
        }

        $args = new ExtendedObject($args);
        $args->template = $this;

        $this->DispatchEvent(EventsContainer::TemplateRendering, (object)['template' => $this, 'args' => $args]);

        ob_start();

        require($this->_file);

        $ret = ob_get_contents();
        ob_end_clean();

        $this->DispatchEvent(EventsContainer::TemplateRendered, (object)['template' => $this, 'content' => $ret]);

        return $ret;

    }

    /**
     * Замена вставок в шаблон
     *
     * @param string $code код для выполнения
     * @param ExtendedObject $args аргументы для передачи в код
     * @return string
     */
    public function RenderCode($code, $args)
    {
        return preg_replace_callback('/\{\?\=(.*?)\?\}/', function ($match) use ($args) {
            return eval('return ' . html_entity_decode($match[1]) . ';');
        }, $code);
    }


}