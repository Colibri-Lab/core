<?php

use PHPUnit\Framework\TestCase;
use Colibri\Collections\ReadonlyCollection;
use Colibri\Collections\CollectionException;

class ReadonlyCollectionTest extends TestCase
{
    private ReadonlyCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new ReadonlyCollection(['a' => 1, 'b' => 2]);
    }

    public function testAddThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->Add('c', 3);
    }

    public function testDeleteThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->Delete('a');
    }

    public function testAppendThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->Append(['c' => 3]);
    }

    public function testInsertThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->Insert(1, 'c', 3);
    }

    public function testDeleteAtThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->DeleteAt(1);
    }

    public function testClearThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->Clear();
    }

    public function testOffsetSetThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->offsetSet('c', 3);
    }

    public function testOffsetUnsetThrowsException(): void
    {
        $this->expectException(CollectionException::class);
        $this->collection->offsetUnset('a');
    }
}
