<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FtpFileSystem\File;
use Colibri\IO\FtpFileSystem\Finder;

class FileTest extends TestCase
{
    private $connectionInfo;
    private $finder;
    private $fileItem;

    protected function setUp(): void
    {
        $this->connectionInfo = (object)[
            'host' => 'ftp.example.com',
            'port' => 21,
            'timeout' => 90,
            'user' => 'username',
            'password' => 'password',
            'passive' => true
        ];
        $this->finder = new Finder($this->connectionInfo);
        $this->fileItem = (object)[
            'name' => '/path/to/file.txt',
            'size' => 1234,
            'perm' => 'rw-r--r--'
        ];
    }

    public function testConstruct()
    {
        $file = new File($this->fileItem, $this->finder->Reconnect(), $this->finder);
        $this->assertInstanceOf(File::class, $file);
    }

    public function testGetProperties()
    {
        $file = new File($this->fileItem, $this->finder->Reconnect(), $this->finder);
        $this->assertEquals('file.txt', $file->name);
        $this->assertEquals('file', $file->filename);
        $this->assertEquals('txt', $file->extension);
        $this->assertEquals('/path/to/file.txt', $file->path);
        $this->assertEquals(1234, $file->size);
        $this->assertEquals('rw-r--r--', $file->access);
    }

    public function testDownload()
    {
        $file = new File($this->fileItem, $this->finder->Reconnect(), $this->finder);
        $this->assertTrue($file->Download('/local/path/to/file.txt'));
    }

    public function testToArray()
    {
        $file = new File($this->fileItem, $this->finder->Reconnect(), $this->finder);
        $array = $file->ToArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('filename', $array);
        $this->assertArrayHasKey('ext', $array);
        $this->assertArrayHasKey('path', $array);
        $this->assertArrayHasKey('size', $array);
    }

    public function testJsonSerialize()
    {
        $file = new File($this->fileItem, $this->finder->Reconnect(), $this->finder);
        $json = $file->jsonSerialize();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('filename', $json);
        $this->assertArrayHasKey('ext', $json);
        $this->assertArrayHasKey('path', $json);
        $this->assertArrayHasKey('size', $json);
    }
}
