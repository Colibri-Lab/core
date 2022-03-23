<?php
namespace PHPTDD\Colibri\Collections;

use Colibri\Collections\ArrayList;
use PHPTDD\BaseTestCase;
use Colibri\Collections\ArrayListIterator;

class ArrayListIteratorTest extends BaseTestCase {

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
     * @covers Colibri\Collections\ArrayListIterator
     **/
    public function testArrayListIterator() {
        $arrayListIterator = new ArrayListIterator(new ArrayList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
        $this->assertEquals(1, $arrayListIterator->current());
    }

    /**
     * @covers Colibri\Collections\ArrayListIterator::valid
     **/
    public function testArrayListIteratorValid() {
        $arrayListIterator = new ArrayListIterator(new ArrayList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
        $this->assertTrue($arrayListIterator->valid());
        $arrayListIterator = new ArrayListIterator(new ArrayList([]));
        $this->assertFalse($arrayListIterator->valid());
    }

    /**
     * @covers Colibri\Collections\ArrayListIterator::next
     **/
    public function testArrayListIteratorNext() {
        $arrayListIterator = new ArrayListIterator(new ArrayList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
        $arrayListIterator->next();        
        $this->assertEquals(2, $arrayListIterator->current());
    }

    /**
     * @covers Colibri\Collections\ArrayListIterator::key
     **/
    public function testArrayListIteratorKey() {
        // code test functionality here
        $arrayListIterator = new ArrayListIterator(new ArrayList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
        $key = $arrayListIterator->key();        
        $this->assertEquals(0, $key);
        $arrayListIterator->next();
        $key = $arrayListIterator->key();        
        $this->assertEquals(1, $key);
    }

    /**
     * @covers Colibri\Collections\ArrayListIterator::current
     **/
    public function testArrayListIteratorCurrent() {
        $arrayListIterator = new ArrayListIterator(new ArrayList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
        $this->assertEquals(1, $arrayListIterator->current());
        $arrayListIterator->next();
        $this->assertEquals(2, $arrayListIterator->current());
    }

    /**
     * @covers Colibri\Collections\ArrayListIterator::rewind
     **/
    public function testArrayListIteratorRewind() {
        $arrayListIterator = new ArrayListIterator(new ArrayList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
        $arrayListIterator->next();
        $arrayListIterator->next();
        $arrayListIterator->next();
        $arrayListIterator->next();
        $this->assertEquals(5, $arrayListIterator->current());

        $arrayListIterator->rewind();
        $this->assertEquals(1, $arrayListIterator->current());
    }
}
