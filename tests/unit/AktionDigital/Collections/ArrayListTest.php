<?php
namespace PHPTDD\Colibri\Collections;
use PHPTDD\BaseTestCase;
use Colibri\Collections\ArrayList;
use Colibri\Collections\ArrayListIterator;

class ArrayListTest extends BaseTestCase {

    /**
     * This code will run before each test executes
     * @return void
     */
    protected function setUp(): void {

    }

    /**
     * This code will run after each test executes
     * @return void
     */
    protected function tearDown(): void {

    }

    /**
     * @covers Colibri\Collections\ArrayList
     **/
    public function testArrayList() {
        $data = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals(count($data), $a->Count());
    }

    /**
     * @covers Colibri\Collections\ArrayList::getIterator
     **/
    public function testArrayListGetIterator() {
        $a = new ArrayList();
        $this->assertTrue($a->getIterator() instanceof ArrayListIterator);
    }

    /**
     * @covers Colibri\Collections\ArrayList::Contains
     **/
    public function testArrayListContains() {
        // code test functionality here
        $data = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data);

        $this->assertTrue($a->Contains('1'));
        $this->assertFalse($a->Contains('2'));
        $this->assertFalse($a->Contains(1));
        $this->assertFalse($a->Contains(10));

    }

    /**
     * @covers Colibri\Collections\ArrayList::IndexOf
     **/
    public function testArrayListIndexOf() {
        // code test functionality here
        $data = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals(4, $a->IndexOf(5));
        $this->assertEquals(0, $a->IndexOf('1'));
    }

    /**
     * @covers Colibri\Collections\ArrayList::Item
     **/
    public function testArrayListItem() {
        // code test functionality here
        $data = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals('1', $a->Item(0));
        $this->assertEquals(2, $a->Item(1));
        $this->assertNull($a->Item(10));
    }

    /**
     * @covers Colibri\Collections\ArrayList::Add
     **/
    public function testArrayListAdd() {
        // code test functionality here
        $data = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals(5, $a->Add(5));
        $this->assertEquals(7, $a->Count()); 
    }

    /**
     * @covers Colibri\Collections\ArrayList::Set
     **/
    public function testArrayListSet() {
        // code test functionality here
        $data = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals(5, $a->Set(3, 5));
        $this->assertEquals(5, $a->Item(3)); 
    }

    /**
     * @covers Colibri\Collections\ArrayList::Append
     **/
    public function testArrayListAppend() {
        // code test functionality here
        $data1 = ['1', 2, 3, 4, 5, 6];
        $data2 = [10, 11, 12, 13];
        $a = new ArrayList($data1);
        $a->Append($data2);
        $this->assertEquals(10, $a->Count());
    }

    /**
     * @covers Colibri\Collections\ArrayList::InsertAt
     **/
    public function testArrayListInsertAt() {
        $data1 = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data1);
        $a->InsertAt(111, 1);
        $this->assertEquals(111, $a->Item(1));
    }

    /**
     * @covers Colibri\Collections\ArrayList::Delete
     **/
    public function testArrayListDelete() {
        $data1 = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data1);
        $a->Delete(4);
        $this->assertNull($a->Item(5));
        $this->assertEquals(5, $a->Count());
    }

    /**
     * @covers Colibri\Collections\ArrayList::DeleteAt
     **/
    public function testArrayListDeleteAt() {
        $data1 = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data1);
        $a->DeleteAt(4);
        $this->assertNull($a->Item(5));
        $this->assertEquals(5, $a->Count());
    }

    /**
     * @covers Colibri\Collections\ArrayList::Clear
     **/
    public function testArrayListClear() {
        $data1 = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data1);
        $this->assertEquals(6, $a->Count());
        $a->Clear();
        $this->assertEquals(0, $a->Count());
    }

    /**
     * @covers Colibri\Collections\ArrayList::ToString
     **/
    public function testArrayListToString4() {
        // code test functionality here
        $data1 = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data1);
        $this->assertEquals(implode(',', $data1), $a->ToString());
        $this->assertEquals(implode(';', $data1), $a->ToString(';'));
    }

    /**
     * @covers Colibri\Collections\ArrayList::ToArray
     **/
    public function testArrayListToArray() {
        // code test functionality here
        // code test functionality here
        $data1 = ['1', 2, 3, 4, 5, 6];
        $a = new ArrayList($data1);
        $data2 = $a->ToArray();

        $this->assertEquals(json_encode($data1), json_encode($data2));

    }

    /**
     * @covers Colibri\Collections\ArrayList::Sort
     **/
    public function testArrayListSort() {
        // code test functionality here
        $data1 = [3, 1, 2, 4, 5, 6];
        
        $data2 = [1, 2, 3, 4, 5, 6];
        $data3 = [6, 5, 4, 3, 2, 1];

        $a = new ArrayList($data1);
        $a->Sort();
        $this->assertEquals(json_encode($data2), json_encode($a->ToArray()));
        $a->Sort(null, SORT_DESC);
        $this->assertEquals(json_encode($data3), json_encode($a->ToArray()));

    }

    /**
     * @covers Colibri\Collections\ArrayList::Count
     **/
    public function testArrayListCount() {
        $data = [1, 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals(count($data), $a->Count());
    }

    /**
     * @covers Colibri\Collections\ArrayList::Last
     **/
    public function testArrayListLast() {
        $data = [1, 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals(1, $a->First());
    }

    /**
     * @covers Colibri\Collections\ArrayList::First
     **/
    public function testArrayListFirst() {
        $data = [1, 2, 3, 4, 5, 6];
        $a = new ArrayList($data);
        $this->assertEquals(6, $a->Last());
    }
}
