<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Cache\CacheManager;
use Colibri\IO\FileSystem\File;

class CacheManagerTest extends TestCase
{
    private $section = 'test_section';
    private $fileName = 'test_file.txt';
    private $fileContent = 'This is a test content';

    public function testPutAndGet()
    {
        $filePath = CacheManager::Put($this->section, $this->fileName, $this->fileContent);
        $this->assertTrue(File::Exists($filePath));
        $this->assertEquals($this->fileContent, CacheManager::Get($this->section, $this->fileName));
        File::Delete($filePath);
    }

    public function testExists()
    {
        $filePath = CacheManager::Put($this->section, $this->fileName, $this->fileContent);
        $this->assertTrue(CacheManager::Exists($this->section, $this->fileName));
        File::Delete($filePath);
        $this->assertFalse(CacheManager::Exists($this->section, $this->fileName));
    }

    public function testGetPath()
    {
        $filePath = CacheManager::GetPath($this->section, $this->fileName);
        $this->assertStringEndsWith($this->fileName, $filePath);
    }
}
