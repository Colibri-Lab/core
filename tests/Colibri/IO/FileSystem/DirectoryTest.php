<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\Directory;

class DirectoryTest extends TestCase
{
    public function testCreate()
    {
        $directory = Directory::Create('/path/to/new/dir');
        $this->assertTrue(Directory::Exists('/path/to/new/dir'));
    }

    public function testDelete()
    {
        Directory::Delete('/path/to/new/dir');
        $this->assertFalse(Directory::Exists('/path/to/new/dir'));
    }

    public function testCopy()
    {
        Directory::Copy('/path/to/source/dir', '/path/to/dest/dir');
        $this->assertTrue(Directory::Exists('/path/to/dest/dir'));
    }

    public function testMove()
    {
        Directory::Move('/path/to/source/dir', '/path/to/new/dir');
        $this->assertTrue(Directory::Exists('/path/to/new/dir'));
    }
}
