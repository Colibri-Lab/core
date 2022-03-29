<?php

namespace Colibri\Web;

use Colibri\Common\VariableHelper;
use Colibri\Data\Storages\Models\DataRow;
use Colibri\Utils\ExtendedObject;
use Colibri\Web\Templates\Template;

class View
{

    /**
     * Конструктор
     * @return void 
     */
    public function __construct()
    {
    // Do nothing
    }

    /**
     * Статический конструктор
     * @var View
     */
    public static function Create(): View
    {
        return new View();
    }

    /**
     * Отображает даннхые по шаблону
     * @param Template шаблон
     * @param ExtendedObject|null $args 
     * @return string 
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
     * Пробует распечатать модель с использованием нужного шаблона
     * @param DataRow $model модель (модель хранилища)
     * @param string $template шаблон (название шаблона в хранилище)
     * @param mixed $args аргументы
     * @return string 
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
