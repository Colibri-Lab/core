<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\RequestedFile;

class RequestedFileTest extends TestCase
{
    public function testIsValid()
    {
        $file = new RequestedFile([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/phpYzdqkD',
            'error' => 0,
            'size' => 123
        ]);
        $this->assertTrue($file->isValid);
    }

    public function testBinary()
    {
        $file = new RequestedFile([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/phpYzdqkD',
            'error' => 0,
            'size' => 123
        ]);
        $this->assertEquals('file content', $file->binary);
    }
}
