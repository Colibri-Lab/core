<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\Security;
use Colibri\IO\FileSystem\File;
use Colibri\Collections\ArrayList;

class SecurityTest extends TestCase
{
    public function testGetProperty()
    {
        $flags = ['read' => true, 'write' => false];
        $security = new Security(new File('/path/to/file'), $flags);

        $this->assertTrue($security->read);
        $this->assertFalse($security->write);
    }

    public function testSetProperty()
    {
        $flags = ['read' => true, 'write' => false];
        $security = new Security(new File('/path/to/file'), $flags);

        $security->write = true;
        $this->assertTrue($security->write);
    }
}
