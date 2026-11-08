<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Models\DataTableIterator;
use Colibri\Collections\ArrayList;
use Colibri\Collections\ArrayListIterator;

class DataTableIteratorTest extends TestCase
{
    public function testExtendsArrayListIterator(): void
    {
        $arrayList = new ArrayList([1, 2, 3]);
        $iterator = new DataTableIterator($arrayList);
        $this->assertInstanceOf(ArrayListIterator::class, $iterator);
    }

    public function testConstructorCreatesInstance(): void
    {
        $arrayList = new ArrayList([1, 2, 3]);
        $iterator = new DataTableIterator($arrayList);
        $this->assertInstanceOf(DataTableIterator::class, $iterator);
    }

    public function testIteration(): void
    {
        $arrayList = new ArrayList([10, 20, 30]);
        $iterator = new DataTableIterator($arrayList);
        $results = [];
        foreach ($iterator as $item) {
            $results[] = $item;
        }
        $this->assertEquals([10, 20, 30], $results);
    }
}
