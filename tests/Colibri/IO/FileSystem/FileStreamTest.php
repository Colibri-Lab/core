<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\FileStream;

class FileStreamTest extends TestCase
{
    public function testRead()
    {
        $fileStream = new FileStream('php://memory');
        $fileStream->Write('Hello, World!');
        $fileStream->Seek(0);

        $this->assertEquals('Hello, World!', $fileStream->Read(0, 13));
    }

    public function testWrite()
    {
        $fileStream = new FileStream('php://memory');
        $bytesWritten = $fileStream->Write('Hello, World!');

        $this->assertEquals(13, $bytesWritten);
    }

    public function testReadLine()
    {
        $fileStream = new FileStream('php://memory');
        $fileStream->Write("Hello, World!\n");
        $fileStream->Seek(0);

        $this->assertEquals("Hello, World!\n", $fileStream->ReadLine());
    }

    public function testWriteLine()
    {
        $fileStream = new FileStream('php://memory');
        $bytesWritten = $fileStream->WriteLine("Hello, World!\n");

        $this->assertEquals(14, $bytesWritten);
    }
}
