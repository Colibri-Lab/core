<?php

use PHPUnit\Framework\TestCase;
use Colibri\Collections\Collection;

class CollectionTest extends TestCase
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

    public function testFirst(): void
    {
        $this->assertEquals(1, $this->collection->First());
    }

    public function testLast(): void
    {
        $this->assertEquals(2, $this->collection->Last());
    }

    public function testAppend(): void
    {
        $this->collection->Append(['c' => 3]);
        $this->assertEquals(3, $this->collection->Item('c'));
    }

    public function testInsert(): void
    {
        $this->collection->Insert(1, 'c', 3);
        $this->assertEquals(3, $this->collection->Item('c'));
    }

    public function testDeleteAt(): void
    {
        $this->collection->DeleteAt(1);
        $this->assertNull($this->collection->ItemAt(1));
    }

    public function testClear(): void
    {
        $this->collection->Clear();
        $this->assertEquals(0, $this->collection->Count());
    }
}
