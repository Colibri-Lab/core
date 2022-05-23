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

/**
 * Класс шаблона
 * 
 * @property-read string $file
 * @property-read string $path
 * 
 */
abstract class Template
{

    use TEventDispatcher;

    const Dummy = 'dummy';

    /**
     * Путь к файлу шаблона
     *
     * @var string
     */
    protected $_file;

    /**
     * Конструктор
     *
     * @param string $file файл шаблона
     */
    public function __construct($file)
    {

        // если обьект нужен без указания файла
        if (strpos($file, Template::Dummy) === 0) {
            return;
        }

        $this->_file = Directory::RealPath($file);
        if (!File::Exists($this->_file)) {
            throw new AppException('Unknown template, file: ' . $file . ' realpath: ' . $this->_file);
        }

    }

    /**
     * Статический конструктор
     * @param mixed $file файл шаблона
     * @return Template созданный шаблон
     */
    public static function Create($file)
    {
        return new static ($file);
    }

    /**
     * Get
     *
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
    {
        if (strtolower($prop) == 'file') {
            return $this->_file;
        }
        else if (strtolower($prop) == 'path') {
            $f = new File($this->_file);
            return $f->directory->path;
        }
        throw new AppException('Unknown property');
    }

    /**
     * Вывод шаблона
     *
     * @param mixed $args
     * @return string
     */
    abstract public function Render($args = null);

    /**
     * Выполняет код
     * @param string $code 
     * @param mixed $args 
     * @return mixed 
     */
    abstract public function RenderCode($code, $args);

    /**
     * Замена вставок в шаблон
     *
     * @param string $code код для выполнения
     * @param mixed $args аргументы для передачи в код
     * @return string
     */
    static function Run($code, $args)
    {
        $dummy = new static (Template::Dummy);
        return $dummy->RenderCode($code, $args);
    }

    /**
     * Запускает шаблон беря за основу путь нахождения текущего шаблона
     * @param string $file Файл подшаблона
     * @return string 
     */
    public function Insert($file, $args = [])
    {
        $currentPathInfo = Directory::PathInfo($this->_file);
        $currentPath = $currentPathInfo['dirname'];
        return static::Create($currentPath . '/' . $file)->Render($args);
    }

}
