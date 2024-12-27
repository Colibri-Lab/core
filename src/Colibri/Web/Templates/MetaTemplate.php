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

use Colibri\Events\EventsContainer;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\ExtendedObject;

/**
 * Class MetaTemplate
 *
 * This class represents a meta template for generating dynamic content.
 *
 */
class MetaTemplate extends Template
{
    public const JS = 'js';
    public const PHP = 'php';

    /**
     * Constructor
     *
     * Initializes a new instance of the MetaTemplate class.
     *
     * @param string $file The template file.
     */
    public function __construct(string $file)
    {
        parent::__construct($file . '.meta');
    }

    /**
     * Renders the template.
     *
     * @param mixed $args Additional arguments for rendering.
     * @return string The rendered output.
     */
    public function Render(mixed $args = null): string
    {

        $args = (object) $args;

        $this->DispatchEvent(EventsContainer::TemplateRendering, (object) ['template' => $this, 'args' => $args]);

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
        } elseif ($args->mode === 'php') {
            $f = eval('return function($mode, $args) {
                ' . $this->_convertToPhp($content) . '
                return $args;
            };');
            // возварщает обьект
            $content = $f($args->mode, $args);
        } else {
            $content = '';
        }

        $this->DispatchEvent(EventsContainer::TemplateRendered, (object) ['template' => $this, 'content' => $content]);

        return $content;

    }

    /**
     * Converts meta-language to JavaScript.
     *
     * @param string $code The meta-language code.
     * @return string The converted JavaScript code.
     */
    private function _convertToJS(string $code): string
    {
        $code = \preg_replace_callback('/\{\{([^\}\}]+)\}\}/s', function ($match) {
            return 'args.' . $match[1];
        }, $code);
        return $code;
    }

    /**
     * Converts meta-language to PHP.
     *
     * @param string $code The meta-language code.
     * @return string The converted PHP code.
     */
    private function _convertToPhp(string $code): string
    {
        $code = \preg_replace_callback('/\{\{([^\}\}]+)\}\}/s', function ($match) {
            return '$args->' . $match[1];
        }, $code);
        return $code;
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
        return $code;
    }

}
