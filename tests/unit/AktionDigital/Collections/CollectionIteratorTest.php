<?php
namespace PHPTDD\Colibri\Collections;

use Colibri\Collections\Collection;
use PHPTDD\BaseTestCase;
use Colibri\Collections\CollectionIterator;

class CollectionIteratorTest extends BaseTestCase {

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
     * @covers Colibri\Collections\CollectionIterator
     **/
    public function testCollectionIterator() {
        $collectionIterator = new CollectionIterator(new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3']));
        $this->assertEquals('Test 1', $collectionIterator->current());
    }

    /**
     * @covers Colibri\Collections\CollectionIterator::rewind
     **/
    public function testCollectionIteratorRewind() {
        $collectionIterator = new CollectionIterator(new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3']));
        $collectionIterator->next();
        $collectionIterator->next();
        $this->assertEquals('Test 3', $collectionIterator->current());

        $collectionIterator->rewind();
        $this->assertEquals('Test 1', $collectionIterator->current());
    }

    /**
     * @covers Colibri\Collections\CollectionIterator::current
     **/
    public function testCollectionIteratorCurrent() {
        $collectionIterator = new CollectionIterator(new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3']));
        $this->assertEquals('Test 1', $collectionIterator->current());
        $collectionIterator->next();
        $this->assertEquals('Test 2', $collectionIterator->current());
    }

    /**
     * @covers Colibri\Collections\CollectionIterator::key
     **/
    public function testCollectionIteratorKey() {
        $collectionIterator = new CollectionIterator(new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3']));
        $key = $collectionIterator->key();        
        $this->assertEquals('test1', $key);
        $collectionIterator->next();
        $key = $collectionIterator->key();        
        $this->assertEquals('test2', $key);
    }

    /**
     * @covers Colibri\Collections\CollectionIterator::next
     **/
    public function testCollectionIteratorNext() {
        $collectionIterator = new CollectionIterator(new Collection(['test1' => 'Test 1', 'test2' => 'Test 2', 'test3' => 'Test 3']));
        $collectionIterator->next();        
        $this->assertEquals('Test 2', $collectionIterator->current());
    }

    /**
     * @covers Colibri\Collections\CollectionIterator::valid
     **/
    public function testCollectionIteratorValid() {
        $collectionIterator = new CollectionIterator(new Collection(['test1' => 'Test 1', 'test2' => 'Test2', 'test3' => 'Test 3']));
        $this->assertTrue($collectionIterator->valid());
        $collectionIterator = new CollectionIterator(new Collection([]));
        $this->assertFalse($collectionIterator->valid());
    }
}
