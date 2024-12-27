<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\Attributes;
use Colibri\IO\FileSystem\File;

class AttributesTest extends TestCase
{
    public function testGetCreated()
    {
        $file = new File('/path/to/file.txt');
        $attributes = new Attributes($file);

        $this->assertIsInt($attributes->created);
    }

    public function testSetCreated()
    {
        $file = new File('/path/to/file.txt');
        $attributes = new Attributes($file);

        $attributes->created = time();
        $this->assertEquals(time(), $attributes->created);
    }
}
