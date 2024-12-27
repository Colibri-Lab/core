<?php

use PHPUnit\Framework\TestCase;
use Colibri\Collections\ArrayList;
use Colibri\Collections\ArrayListIterator;

class ArrayListIteratorTest extends TestCase
{
    private ArrayListIterator $iterator;

    protected function setUp(): void
    {
        $arrayList = new ArrayList([1, 2, 3]);
        $this->iterator = new ArrayListIterator($arrayList);
    }

    public function testRewind(): void
    {
        $this->iterator->next();
        $this->iterator->rewind();
        $this->assertEquals(1, $this->iterator->current());
    }

    public function testCurrent(): void
    {
        $this->assertEquals(1, $this->iterator->current());
    }

    public function testKey(): void
    {
        $this->assertEquals(0, $this->iterator->key());
    }

    public function testNext(): void
    {
        $this->iterator->next();
        $this->assertEquals(2, $this->iterator->current());
    }

    public function testValid(): void
    {
        $this->assertTrue($this->iterator->valid());
        $this->iterator->next();
        $this->iterator->next();
        $this->iterator->next();
        $this->assertFalse($this->iterator->valid());
    }
}
