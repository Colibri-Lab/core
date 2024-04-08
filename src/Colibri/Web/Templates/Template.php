<?php

/**
 * Templates
 *
 * This abstract class represents a template for web content generation.
 *
 * @package Colibri\Web\Templates
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab 
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
 * Template class
 *
 * @property-read string $file The file path of the template.
 * @property-read string $path The directory path of the template.
 */
abstract class Template
{

    use TEventDispatcher;

    const Dummy = 'dummy';

    /**
     * The path to the template file.
     *
     * @var string
     */
    protected $_file;

    /**
     * Constructor
     *
     * Initializes a new instance of the Template class.
     *
     * @param mixed $file The template file.
     * @throws AppException If the template file does not exist.
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
     * Static constructor
     *
     * Creates a new instance of the Template class.
     *
     * @param mixed $file The template file.
     * @return Template The created template instance.
     */
    public static function Create($file)
    {
        return new static ($file);
    }

    /**
     * Magic getter method
     *
     * Retrieves the value of a property.
     *
     * @param string $prop The property name.
     * @return mixed The value of the property.
     * @throws AppException If the property is unknown.
     */
    public function __get($prop)
    {
        if (strtolower($prop) == 'file') {
            return $this->_file;
        } elseif (strtolower($prop) == 'path') {
            $f = new File($this->_file);
            return $f->directory->path;
        }
        throw new AppException('Unknown property');
    }

    /**
     * Renders the template.
     *
     * @param mixed $args Additional arguments for rendering.
     * @return string The rendered output.
     */
    abstract public function Render($args = null);

    /**
     * Executes code.
     *
     * @param string $code The code to execute.
     * @param mixed $args The arguments for the code.
     * @return mixed The result of the code execution.
     */
    abstract public function RenderCode(string $code, mixed $args): mixed;

    /**
     * Runs template based on the current template's directory path.
     *
     * @param string $file The sub-template file.
     * @param mixed $args Additional arguments for rendering.
     * @return string The rendered output.
     */
    static function Run(string $code, mixed $args): string
    {
        $dummy = new static (Template::Dummy);
        return $dummy->RenderCode($code, $args);
    }

    /**
     * Runs code in a dummy template.
     *
     * @param string $code The code to execute.
     * @param mixed $args The arguments for the code.
     * @return string The rendered output.
     */
    public function Insert(string $file, mixed $args = [])
    {
        $currentPathInfo = Directory::PathInfo($this->_file);
        $currentPath = $currentPathInfo['dirname'];
        return static::Create($currentPath . '/' . $file)->Render($args);
    }

}