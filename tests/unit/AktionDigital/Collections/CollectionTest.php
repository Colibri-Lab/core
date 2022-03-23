<?php
namespace PHPTDD\Colibri\Collections;
use PHPTDD\BaseTestCase;
use Colibri\Collections\Collection;
use Colibri\Collections\CollectionIterator;

class CollectionTest extends BaseTestCase {

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
     * @covers Colibri\Collections\Collection
     **/
    public function testCollection() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals(2, $collection->Count());
    }

    /**
     * @covers Colibri\Collections\Collection::Exists
     **/
    public function testCollectionExists() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertTrue($collection->Exists('test1'));
        $this->assertTrue($collection->Exists('test2'));
        $this->assertFalse($collection->Exists('test3'));
    }

    /**
     * @covers Colibri\Collections\Collection::Contains
     **/
    public function testCollectionContains() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertTrue($collection->Contains('Test 1'));
        $this->assertTrue($collection->Contains('Test 2'));
        $this->assertFalse($collection->Contains('Test 3'));
    }

    /**
     * @covers Colibri\Collections\Collection::IndexOf
     **/
    public function testCollectionIndexOf() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals(0, $collection->IndexOf('Test 1'));
        $this->assertEquals(1, $collection->IndexOf('Test 2'));
        $this->assertNull($collection->IndexOf('Test 3'));
    }

    /**
     * @covers Colibri\Collections\Collection::Key
     **/
    public function testCollectionKey() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals('test1', $collection->Key(0));
        $this->assertEquals('test2', $collection->Key(1));
        $this->assertNull($collection->Key(2));
        $this->assertNull($collection->Key(-1));
    }

    /**
     * @covers Colibri\Collections\Collection::Item
     **/
    public function testCollectionItem() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals('Test 1', $collection->Item('test1'));
        $this->assertEquals('Test 2', $collection->Item('test2'));
        $this->assertNull($collection->Item('test3'));
    }

    /**
     * @covers Colibri\Collections\Collection::ItemAt
     **/
    public function testCollectionItemAt() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals('Test 1', $collection->ItemAt(0));
        $this->assertEquals('Test 2', $collection->ItemAt(1));
        $this->assertNull($collection->ItemAt(2));
    }

    /**
     * @covers Colibri\Collections\Collection::getIterator
     **/
    public function testCollectionGetIterator() {
        // code test functionality here
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertTrue($collection->getIterator() instanceof CollectionIterator);
    }

    /**
     * @covers Colibri\Collections\Collection::Add
     **/
    public function testCollectionAdd() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $collection->Add('test3', 'Test 3');
        $this->assertEquals(3, $collection->Count());
        $this->assertEquals('test3', $collection->Key(2));
        $this->assertEquals('Test 3', $collection->ItemAt(2));
    }

    /**
     * @covers Colibri\Collections\Collection::Append
     **/
    public function testCollectionAppend() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);

        $collection->Append(['test3' => 'Test 3', 'test4' => 'Test 4']);
        $this->assertEquals(4, $collection->Count());
        $this->assertEquals('test3', $collection->Key(2));
        $this->assertEquals('test4', $collection->Key(3));
        $this->assertEquals('Test 3', $collection->ItemAt(2));
        $this->assertEquals('Test 4', $collection->ItemAt(3));

        $collection->Append(new Collection(['test3' => 'Test 3', 'test4' => 'Test 4']));
        $this->assertEquals(4, $collection->Count());
        $this->assertEquals('test3', $collection->Key(2));
        $this->assertEquals('test4', $collection->Key(3));
        $this->assertEquals('Test 3', $collection->ItemAt(2));
        $this->assertEquals('Test 4', $collection->ItemAt(3));

        $collection->Append(new Collection(['test5' => 'Test 5', 'test6' => 'Test 6']));
        $this->assertEquals(6, $collection->Count());

    }

    /**
     * @covers Colibri\Collections\Collection::Insert
     **/
    public function testCollectionInsert() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals('test2', $collection->Key(1));
        $this->assertEquals('Test 2', $collection->ItemAt(1));
        $collection->Insert(1, 'test4', 'Test 4');
        $this->assertEquals('test4', $collection->Key(1));
        $this->assertEquals('Test 4', $collection->ItemAt(1));
    }

    /**
     * @covers Colibri\Collections\Collection::Delete
     **/
    public function testCollectionDelete() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4']);
        $collection->Delete('test3');
        $this->assertNull($collection->Item('test3'));
    }

    /**
     * @covers Colibri\Collections\Collection::DeleteAt
     **/
    public function testCollectionDeleteAt() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4']);
        $collection->DeleteAt(1);
        $this->assertNull($collection->Item('test2'));
    }

    /**
     * @covers Colibri\Collections\Collection::Clear
     **/
    public function testCollectionClear() {
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4']);
        $this->assertEquals(4, $collection->Count());
        $collection->Clear();
        $this->assertEquals(0, $collection->Count());
    }

    /**
     * @covers Colibri\Collections\Collection::ToString
     **/
    public function testCollectionToString() {
        // code test functionality here
        $collection = new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4']);
        $string1 = $collection->ToString();
        $string2 = $collection->ToString(['=', '&']);
        $string3 = $collection->ToString(['=', '&'], function($k, $v) {
            return $k.$v;
        });

        $this->assertEquals('test1Test 1test2Test 2test3Test 3test4Test 4', $string1);
        $this->assertEquals('test1=Test 1&test2=Test 2&test3=Test 3&test4=Test 4', $string2);
        $this->assertEquals('test1=test1Test 1&test2=test2Test 2&test3=test3Test 3&test4=test4Test 4', $string3);

    }

    /**
     * @covers Colibri\Collections\Collection::FromString
     **/
    public function testCollectionFromString() {
        $collection = Collection::FromString('test1=Test 1&test2=Test 2&test3=Test 3&test4=Test 4', ['=', '&']);
        $string = $collection->ToString(['=', '&']);
        $this->assertEquals('test1=Test 1&test2=Test 2&test3=Test 3&test4=Test 4', $string);

        $collection = Collection::FromString('test1=Test 1&test2=Test 2&test3=Test 3&test4=Test 4');
        $string = $collection->ToString(['=', '&']);
        $this->assertEquals('', $string);
    }

    /**
     * @covers Colibri\Collections\Collection::ToArray
     **/
    public function testCollectionToArray() {
        $data = ['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4'];
        $collection = new Collection($data);
        $this->assertEquals(json_encode($data), json_encode($collection->ToArray()));
    }

    /**
     * @covers Colibri\Collections\Collection::Count
     **/
    public function testCollectionCount() {
        $data = ['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4'];
        $collection = new Collection($data);
        $this->assertEquals(4, $collection->Count());
    }

    /**
     * @covers Colibri\Collections\Collection::First
     **/
    public function testCollectionFirst() {
        $data = ['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4'];
        $collection = new Collection($data);
        $this->assertEquals('Test 1', $collection->First());
    }

    /**
     * @covers Colibri\Collections\Collection::Last
     **/
    public function testCollectionLast() {
        $data = ['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3', 'test4' => 'Test 4'];
        $collection = new Collection($data);
        $this->assertEquals('Test 4', $collection->Last());
    }
}
