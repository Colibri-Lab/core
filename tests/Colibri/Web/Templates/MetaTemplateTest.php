<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Templates\MetaTemplate;

class MetaTemplateTest extends TestCase
{
    public function testRenderTemplate()
    {
        $template = new MetaTemplate('dummy');
        $output = $template->Render(['mode' => 'php']);
        $this->assertIsString($output);
    }

    public function testRenderCode()
    {
        $template = new MetaTemplate('dummy');
        $code = '{{key}}';
        $output = $template->RenderCode($code, ['key' => 'value']);
        $this->assertEquals('{{key}}', $output);
    }
}
