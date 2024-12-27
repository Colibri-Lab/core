<?php

use PHPUnit\Framework\TestCase;
use Colibri\Collections\Collection;
use Colibri\Collections\CollectionIterator;

class CollectionIteratorTest extends TestCase
{
    private CollectionIterator $iterator;

    protected function setUp(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2]);
        $this->iterator = new CollectionIterator($collection);
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
        $this->assertEquals('a', $this->iterator->key());
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
        $this->assertFalse($this->iterator->valid());
    }
}
