<?php
namespace PHPTDD\Colibri\Events;
use PHPTDD\BaseTestCase;
use Colibri\Events\EventDispatcher;

class EventDispatcherTest extends BaseTestCase {

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
     * @covers Colibri\Events\EventDispatcher
     **/
    public function testEventDispatcher() {
       
        $dispatcher = EventDispatcher::Create();
        $this->assertTrue(EventDispatcher::$instance instanceof EventDispatcher);
        $this->assertEquals(EventDispatcher::$instance, $dispatcher);
        try {
            $dispatcher = new EventDispatcher();
            $this->fail();
        }
        catch(\Throwable $e) {

        }
    }

    /**
     * @covers Colibri\Events\EventDispatcher::Dispose
     **/
    public function testEventDispatcherDispose() {
        $dispatcher = EventDispatcher::Create();
        $dispatcher->AddEventListener('event1', 'testEventDispatcherDispose');

        $this->assertTrue($dispatcher->HasEventListener('event1', 'testEventDispatcherDispose'));

        $dispatcher->Dispose();
        
        $this->assertFalse($dispatcher->HasEventListener('event1', 'testEventDispatcherDispose'));

    }

    /**
     * @covers Colibri\Events\EventDispatcher::AddEventListener
     **/
    public function testEventDispatcherAddEventListener() {
        $dispatcher = EventDispatcher::Create();
        $dispatcher->AddEventListener('event1', 'testEventDispatcherDispose');
        $this->assertTrue($dispatcher->HasEventListener('event1', 'testEventDispatcherDispose'));

        $this->assertFalse($dispatcher->AddEventListener(123123, 'testEventDispatcherDispose'));
        $this->assertFalse($dispatcher->AddEventListener(null, 123123));

    }

    /**
     * @covers Colibri\Events\EventDispatcher::RemoveEventListener
     **/
    public function testEventDispatcherRemoveEventListener() {
        $dispatcher = EventDispatcher::Create();
        $dispatcher->AddEventListener('event1', 'testEventDispatcherDispose');

        $this->assertTrue($dispatcher->HasEventListener('event1', 'testEventDispatcherDispose'));

        $this->assertTrue($dispatcher->RemoveEventListener('event1', 'testEventDispatcherDispose'));

        $this->assertFalse($dispatcher->HasEventListener('event1', 'testEventDispatcherDispose'));

        $dispatcher->AddEventListener('event1', 'testEventDispatcherDispose');
        $this->assertFalse($dispatcher->RemoveEventListener(null, 'testEventDispatcherDispose'));
        $this->assertFalse($dispatcher->RemoveEventListener(123123, 'testEventDispatcherDispose'));

    }

    /**
     * @covers Colibri\Events\EventDispatcher::Dispatch
     **/
    public function testEventDispatcherDispatch() {
        // code test functionality here
    }

    /**
     * @covers Colibri\Events\EventDispatcher::HasEventListener
     **/
    public function testEventDispatcherHasEventListener() {
        
        $dispatcher = EventDispatcher::Create();
        $dispatcher->AddEventListener('event1', 'testEventDispatcherDispose');

        $this->assertTrue($dispatcher->HasEventListener('event1', 'testEventDispatcherDispose'));

        $this->assertFalse($dispatcher->HasEventListener(123123123, 'testEventDispatcherDispose'));
        $this->assertFalse($dispatcher->HasEventListener(null, 'testEventDispatcherDispose'));
    }

    /**
     * @covers Colibri\Events\EventDispatcher::RegisteredListeners
     **/
    public function testEventDispatcherRegisteredListeners() {
        // code test functionality here
    }
}
