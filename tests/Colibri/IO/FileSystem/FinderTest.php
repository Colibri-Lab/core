<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\Finder;
use Colibri\Collections\ArrayList;

class FinderTest extends TestCase
{
    public function testFiles()
    {
        $finder = new Finder();
        $files = $finder->Files('/path/to/dir', '/\.txt$/');

        $this->assertInstanceOf(ArrayList::class, $files);
    }

    public function testFilesRecursive()
    {
        $finder = new Finder();
        $files = $finder->FilesRecursive('/path/to/dir', '/\.txt$/');

        $this->assertInstanceOf(ArrayList::class, $files);
    }

    public function testDirectories()
    {
        $finder = new Finder();
        $directories = $finder->Directories('/path/to/dir');

        $this->assertInstanceOf(ArrayList::class, $directories);
    }

    public function testDirectoriesRecursive()
    {
        $finder = new Finder();
        $directories = $finder->DirectoriesRecursive('/path/to/dir');

        $this->assertInstanceOf(ArrayList::class, $directories);
    }
}
