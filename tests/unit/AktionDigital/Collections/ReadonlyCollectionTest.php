<?php
namespace PHPTDD\Colibri\Collections;
use PHPTDD\BaseTestCase;
use Colibri\Collections\ReadonlyCollection;
use Colibri\Collections\CollectionException;

class ReadonlyCollectionTest extends BaseTestCase {

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
     * @covers Colibri\Collections\ReadonlyCollection
     **/
    public function testReadonlyCollection() {
        $collection = new ReadonlyCollection(['test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals(2, $collection->Count());
    }

    /**
     * @covers Colibri\Collections\ReadonlyCollection::Clean
     **/
    public function testReadonlyCollectionClean() {
        $collection = new ReadonlyCollection(['empty' => '', 'test1' => 'Test 1', 'test2' => 'Test 2']);
        $this->assertEquals(3, $collection->Count());
        $collection->Clean();
        $this->assertEquals(2, $collection->Count());
    }

    /**
     * @covers Colibri\Collections\ReadonlyCollection::Add
     **/
    public function testReadonlyCollectionAdd() {
        $collection = new ReadonlyCollection(['empty' => '', 'test1' => 'Test 1', 'test2' => 'Test 2']);
        try {
            $collection->Add('test', 'Test');
            $this->fail();
        }
        catch(CollectionException $e) {
        }
    }

    /**
     * @covers Colibri\Collections\ReadonlyCollection::Delete
     **/
    public function testReadonlyCollectionDelete() {
        $collection = new ReadonlyCollection(['empty' => '', 'test1' => 'Test 1', 'test2' => 'Test 2']);
        try {
            $collection->Delete('empty');
            $this->fail();
        }
        catch(CollectionException $e) {
        }
    }
}
