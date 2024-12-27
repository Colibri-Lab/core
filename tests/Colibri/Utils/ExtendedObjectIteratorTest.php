<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\ExtendedObjectIterator;

class ExtendedObjectIteratorTest extends TestCase
{
    public function testIterator()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        $iterator = new ExtendedObjectIterator($data);

        $iterator->rewind();
        $this->assertEquals(1, $iterator->current());
        $this->assertEquals('a', $iterator->key());

        $iterator->next();
        $this->assertEquals(2, $iterator->current());
        $this->assertEquals('b', $iterator->key());

        $iterator->next();
        $this->assertEquals(3, $iterator->current());
        $this->assertEquals('c', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
