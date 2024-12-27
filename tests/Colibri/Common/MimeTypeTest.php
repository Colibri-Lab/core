<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\MimeType;

class MimeTypeTest extends TestCase
{
    public function testCreate()
    {
        $mimeType = MimeType::Create('file.jpg');
        $this->assertEquals('image/jpeg', $mimeType->data);
    }

    public function testGetType()
    {
        $this->assertEquals('jpg', MimeType::GetType('image/jpeg'));
    }

    public function testGetTypeFromFileName()
    {
        $this->assertEquals('jpg', MimeType::GetTypeFromFileName('file.jpg'));
    }
}
