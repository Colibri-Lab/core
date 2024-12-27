<?php

use PHPUnit\Framework\TestCase;
use Colibri\Collections\ArrayList;

class IArrayListTest extends TestCase
{
    private ArrayList $arrayList;

    protected function setUp(): void
    {
        $this->arrayList = new ArrayList([1, 2, 3]);
    }

    public function testItem(): void
    {
        $this->assertEquals(2, $this->arrayList->Item(1));
        $this->assertNull($this->arrayList->Item(10));
    }

    public function testAdd(): void
    {
        $this->arrayList->Add(4);
        $this->assertEquals(4, $this->arrayList->Item(3));
    }

    public function testAppend(): void
    {
        $this->arrayList->Append([4, 5]);
        $this->assertEquals(5, $this->arrayList->Item(4));
    }

    public function testDelete(): void
    {
        $this->arrayList->Delete(2);
        $this->assertFalse($this->arrayList->Contains(2));
    }

    public function testDeleteAt(): void
    {
        $this->arrayList->DeleteAt(1);
        $this->assertNull($this->arrayList->Item(1));
    }

    public function testToString(): void
    {
        $this->assertEquals('1,2,3', $this->arrayList->ToString());
    }

    public function testToArray(): void
    {
        $this->assertEquals([1, 2, 3], $this->arrayList->ToArray());
    }

    public function testCount(): void
    {
        $this->assertEquals(3, $this->arrayList->Count());
    }

    public function testFirst(): void
    {
        $this->assertEquals(1, $this->arrayList->First());
    }

    public function testLast(): void
    {
        $this->assertEquals(3, $this->arrayList->Last());
    }
}
