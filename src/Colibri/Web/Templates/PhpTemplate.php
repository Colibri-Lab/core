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

class PhpTemplate extends Template
{
    /**
     * Constructor
     *
     * Initializes a new instance of the PhpTemplate class.
     *
     * @param mixed $file The template file.
     */
    public function __construct($file)
    {
        parent::__construct($file . '.php');
    }

    /**
     * Renders the template.
     *
     * @param mixed $args Additional arguments for rendering.
     * @return string The rendered output.
     * @throws AppException If the template file does not exist.
     */
    public function Render($args = null)
    {

        if (!File::Exists($this->_file)) {
            throw new AppException('Unknown template, realpath: ' . $this->_file);
        }

        $args = new ExtendedObject($args);
        $args->template = $this;

        $this->DispatchEvent(EventsContainer::TemplateRendering, (object) ['template' => $this, 'args' => $args]);

        ob_start();

        require($this->_file);

        $ret = ob_get_contents();
        ob_end_clean();

        $args = (object) ['template' => $this, 'content' => $ret];
        $this->DispatchEvent(EventsContainer::TemplateRendered, $args);
        if (isset($args->content)) {
            $ret = $args->content;
        }

        return $ret;

    }

    /**
     * Renders code in the template.
     *
     * @param string $code The code to render.
     * @param mixed $args The arguments for the code.
     * @return mixed The rendered output.
     */
    public function RenderCode(string $code, mixed $args): mixed
    {
        return preg_replace_callback('/\{\?\=(.*?)\?\}/', function ($match) use ($args) {
            return eval('return ' . html_entity_decode($match[1]) . ';');
        }, $code);
    }


}
