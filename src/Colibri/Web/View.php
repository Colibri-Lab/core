<?php

/**
 * Web
 *
 * This abstract class represents a template for web content generation.
 *
 * @package Colibri\Web
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 */

namespace Colibri\Web;

use Colibri\Common\VariableHelper;
use Colibri\Data\Storages\Models\DataRow;
use Colibri\Utils\ExtendedObject;
use Colibri\Web\Templates\Template;

/**
 * Class View
 *
 * This class represents a view for rendering data using templates.
 */
class View
{
    /**
     * Constructor
     *
     * Initializes a new instance of the View class.
     *
     * @return void
     */
    public function __construct()
    {
        // Do nothing
    }

    /**
     * Static constructor
     *
     * Creates a new instance of the View class.
     *
     * @return View The created View instance.
     */
    public static function Create(): View
    {
        return new View();
    }

    /**
     * Renders data using a template.
     *
     * @param Template $template The template to use for rendering.
     * @param ExtendedObject|null $args Additional arguments for rendering.
     * @return string The rendered output.
     */
    public function Render(Template $template, ?ExtendedObject $args = null)
    {

        if (VariableHelper::IsNull($args)) {
            $args = new ExtendedObject();
        }

        $args->view = $this;

        return $template->Render($args);
    }

    /**
     * Tries to render a model using the specified template.
     *
     * @param DataRow $model The model to render.
     * @param string $template The template name.
     * @param mixed $args Additional arguments.
     * @return string The rendered output.
     */
    public function RenderModel(DataRow $model, string $template = 'default', mixed $args = []): string
    {
        $templates = $model->Storage()->GetTemplates();

        if (!isset($templates->class) || !class_exists($templates->class)) {
            return '';
        }

        $templateClass = $templates->class;
        $defaultTemplate = $templates->templates->default;
        $templateFiles = $templates->templates->files;

        $args = new ExtendedObject($args);
        $args->model = $model;

        if ($template == 'default' || !isset($templateFiles->$template)) {
            return $this->Render(new $templateClass($defaultTemplate), $args);
        }

        return $this->Render(new $templateClass($templateFiles->$template), $args);

    }
}
