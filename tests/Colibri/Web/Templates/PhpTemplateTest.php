<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Templates\PhpTemplate;
use Colibri\AppException;

class PhpTemplateTest extends TestCase
{
    public function testRenderTemplate()
    {
        $template = new PhpTemplate('dummy');
        $output = $template->Render(['key' => 'value']);
        $this->assertIsString($output);
    }

    public function testRenderCode()
    {
        $template = new PhpTemplate('dummy');
        $code = '<?= $args["key"] ?>';
        $output = $template->RenderCode($code, ['key' => 'value']);
        $this->assertEquals('value', $output);
    }
}
