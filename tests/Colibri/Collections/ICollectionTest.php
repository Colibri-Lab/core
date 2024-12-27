<?php

use PHPUnit\Framework\TestCase;
use Colibri\Collections\Collection;

class ICollectionTest extends TestCase
{
    private Collection $collection;

    protected function setUp(): void
    {
        $this->collection = new Collection(['a' => 1, 'b' => 2]);
    }

    public function testExists(): void
    {
        $this->assertTrue($this->collection->Exists('a'));
        $this->assertFalse($this->collection->Exists('c'));
    }

    public function testKey(): void
    {
        $this->assertEquals('a', $this->collection->Key(0));
        $this->assertNull($this->collection->Key(10));
    }

    public function testItem(): void
    {
        $this->assertEquals(1, $this->collection->Item('a'));
        $this->assertNull($this->collection->Item('c'));
    }

    public function testItemAt(): void
    {
        $this->assertEquals(2, $this->collection->ItemAt(1));
        $this->assertNull($this->collection->ItemAt(10));
    }

    public function testAdd(): void
    {
        $this->collection->Add('c', 3);
        $this->assertEquals(3, $this->collection->Item('c'));
    }

    public function testDelete(): void
    {
        $this->collection->Delete('a');
        $this->assertFalse($this->collection->Exists('a'));
    }

    public function testToString(): void
    {
        $this->assertEquals('a=1&b=2', $this->collection->ToString(['=', '&']));
    }

    public function testToArray(): void
    {
        $this->assertEquals(['a' => 1, 'b' => 2], $this->collection->ToArray());
    }

    public function testCount(): void
    {
        $this->assertEquals(2, $this->collection->Count());
    }
}
