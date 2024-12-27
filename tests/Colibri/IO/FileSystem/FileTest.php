<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\File;

class FileTest extends TestCase
{
    public function testRead()
    {
        $content = File::Read('/path/to/file.txt');
        $this->assertEquals('file content', $content);
    }

    public function testWrite()
    {
        File::Write('/path/to/file.txt', 'new content');
        $this->assertEquals('new content', file_get_contents('/path/to/file.txt'));
    }

    public function testAppend()
    {
        File::Append('/path/to/file.txt', ' appended');
        $this->assertEquals('file content appended', file_get_contents('/path/to/file.txt'));
    }

    public function testExists()
    {
        $this->assertTrue(File::Exists('/path/to/file.txt'));
    }

    public function testDelete()
    {
        File::Delete('/path/to/file.txt');
        $this->assertFalse(File::Exists('/path/to/file.txt'));
    }
}
