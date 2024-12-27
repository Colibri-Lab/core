<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Templates\Template;
use Colibri\AppException;

class TemplateTest extends TestCase
{
    public function testCreateTemplate()
    {
        $template = $this->getMockForAbstractClass(Template::class, ['dummy']);
        $this->assertInstanceOf(Template::class, $template);
    }

    public function testCreateTemplateWithInvalidFile()
    {
        $this->expectException(AppException::class);
        $this->getMockForAbstractClass(Template::class, ['invalid_file']);
    }

    public function testGetFileProperty()
    {
        $template = $this->getMockForAbstractClass(Template::class, ['dummy']);
        $this->assertEquals('dummy', $template->file);
    }

    public function testGetPathProperty()
    {
        $template = $this->getMockForAbstractClass(Template::class, ['dummy']);
        $this->assertNotEmpty($template->path);
    }
}
