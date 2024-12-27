<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\View;
use Colibri\Web\Templates\Template;
use Colibri\Utils\ExtendedObject;
use Colibri\Data\Storages\Models\DataRow;

class ViewTest extends TestCase
{
    public function testCreate()
    {
        $view = View::Create();
        $this->assertInstanceOf(View::class, $view);
    }

    public function testRender()
    {
        $template = $this->createMock(Template::class);
        $template->method('Render')->willReturn('rendered content');

        $view = new View();
        $result = $view->Render($template, new ExtendedObject());

        $this->assertEquals('rendered content', $result);
    }

    public function testRenderModel()
    {
        $model = $this->createMock(DataRow::class);
        $model->method('Storage')->willReturn((object)[
            'GetTemplates' => (object)[
                'class' => Template::class,
                'templates' => (object)[
                    'default' => 'default template',
                    'files' => (object)[]
                ]
            ]
        ]);

        $view = new View();
        $result = $view->RenderModel($model);

        $this->assertEquals('rendered content', $result);
    }
}
