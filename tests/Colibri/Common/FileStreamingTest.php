<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\FileStreaming;

class FileStreamingTest extends TestCase
{
    public function testToBase64()
    {
        $filePath = __DIR__ . '/test_file.txt';
        file_put_contents($filePath, 'test content');
        $base64 = FileStreaming::ToBase64($filePath);
        $this->assertStringStartsWith('data:', $base64);
        unlink($filePath);
    }

    public function testAsText()
    {
        $filePath = __DIR__ . '/test_file.txt';
        file_put_contents($filePath, 'test content');
        $text = FileStreaming::AsText($filePath);
        $this->assertEquals('test content', $text);
        unlink($filePath);
    }

    public function testAsTag()
    {
        $filePath = __DIR__ . '/test_file.txt';
        file_put_contents($filePath, 'test content');
        $tag = FileStreaming::AsTag($filePath);
        $this->assertStringStartsWith('<img', $tag);
        unlink($filePath);
    }
}
