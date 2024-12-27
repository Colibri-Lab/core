<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\Stream;

class StreamTest extends TestCase
{
    public function testGetLength()
    {
        $stream = $this->getMockForAbstractClass(Stream::class);
        $reflection = new ReflectionClass($stream);
        $property = $reflection->getProperty('_length');
        $property->setAccessible(true);
        $property->setValue($stream, 123);

        $this->assertEquals(123, $stream->length);
    }

    public function testDestructor()
    {
        $stream = $this->getMockForAbstractClass(Stream::class);
        $reflection = new ReflectionClass($stream);
        $property = $reflection->getProperty('_stream');
        $property->setAccessible(true);
        $property->setValue($stream, fopen('php://memory', 'r+'));

        $stream->__destruct();
        $this->assertNull($property->getValue($stream));
    }
}
