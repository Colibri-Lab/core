<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Cache\Bundle;
use Colibri\IO\FileSystem\File;
use Colibri\IO\FileSystem\Directory;

class BundleTest extends TestCase
{
    private $bundleName = 'test_bundle';
    private $exts = ['js', 'css'];
    private $path = '/path/to/assets';
    private $exception = ['exclude_dir'];

    public function testCompile()
    {
        $compiledPath = Bundle::Compile($this->bundleName, $this->exts, $this->path, $this->exception);
        $this->assertStringContainsString($this->bundleName, $compiledPath);
    }

    public function testLastModified()
    {
        $lastModified = Bundle::LastModified($this->bundleName, $this->exts, $this->path, $this->exception);
        $this->assertIsInt($lastModified);
    }

    public function testCompileFiles()
    {
        $files = ['/path/to/file1.js', '/path/to/file2.css'];
        $compiledPath = Bundle::CompileFiles($this->bundleName, $this->exts, $files);
        $this->assertStringContainsString($this->bundleName, $compiledPath);
    }

    public function testGetNamespaceAssets()
    {
        $assets = Bundle::GetNamespaceAssets($this->path, $this->exts, $this->exception);
        $this->assertIsArray($assets);
    }

    public function testGetChildAssets()
    {
        $assets = Bundle::GetChildAssets($this->path, $this->exts, $this->exception);
        $this->assertIsArray($assets);
    }

    public function testAutomate()
    {
        $domain = 'test_domain';
        $ar = [
            ['path' => '/path/to/assets1', 'exts' => ['js'], 'exception' => [], 'preg' => false],
            ['path' => '/path/to/assets2', 'exts' => ['css'], 'exception' => [], 'preg' => false]
        ];
        $compiledPath = Bundle::Automate($domain, $this->bundleName, 'js', $ar);
        $this->assertStringContainsString($this->bundleName, $compiledPath);
    }
}
