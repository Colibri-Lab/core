<?php
namespace PHPTDD\Colibri\Events;
use PHPTDD\BaseTestCase;
use Colibri\Events\Event;
use InvalidArgumentException;

class EventTest extends BaseTestCase {

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
     * @covers Colibri\Events\Event
     **/
    public function testEvent() {
        // code test functionality here
        $e = new Event((object)['test' => 1], 'событие');
        $this->assertTrue($e instanceof Event);

        try {
            $e = new Event('adfasdf', 'событие');
            $this->fail();
        }
        catch(InvalidArgumentException $e) {

        }

        try {
            $e = new Event(123123, 'событие');
            $this->fail();
        }
        catch(InvalidArgumentException $e) {

        }

        try {
            $e = new Event((object)['test' => 1], 123123);
            $this->fail();
        }
        catch(InvalidArgumentException $e) {

        }

        try {
            $e = new Event(null, 123123);
            $this->fail();
        }
        catch(InvalidArgumentException $e) {

        }


    }

}
