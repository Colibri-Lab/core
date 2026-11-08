<?php

use PHPUnit\Framework\TestCase;
use Colibri\Events\EventDispatcher;
use Colibri\Events\Event;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = EventDispatcher::Instance();
        $this->dispatcher->Clear();
    }

    protected function tearDown(): void
    {
        $this->dispatcher->Clear();
    }

    public function testInstanceReturnsSingleton(): void
    {
        $instance1 = EventDispatcher::Instance();
        $instance2 = EventDispatcher::Instance();
        $this->assertSame($instance1, $instance2);
    }

    public function testAddEventListener(): void
    {
        $called = false;
        $result = $this->dispatcher->AddEventListener('test.event', function (Event $event, mixed $args) use (&$called) {
            $called = true;
        });
        $this->assertTrue($result);
    }

    public function testDispatchCallsListener(): void
    {
        $called = false;
        $this->dispatcher->AddEventListener('test.dispatch', function (Event $event, mixed $args) use (&$called) {
            $called = true;
            return true;
        });
        $sender = new \stdClass();
        $this->dispatcher->Dispatch(new Event($sender, 'test.dispatch'));
        $this->assertTrue($called);
    }

    public function testDispatchPassesEventAndArgs(): void
    {
        $receivedEvent = null;
        $receivedArgs = null;
        $sender = new \stdClass();
        $args = ['key' => 'value'];

        $this->dispatcher->AddEventListener('test.args', function (Event $event, mixed $passedArgs) use (&$receivedEvent, &$receivedArgs) {
            $receivedEvent = $event;
            $receivedArgs = $passedArgs;
            return true;
        });

        $this->dispatcher->Dispatch(new Event($sender, 'test.args'), $args);
        $this->assertInstanceOf(Event::class, $receivedEvent);
        $this->assertNotNull($receivedArgs);
    }

    public function testAddEventListenerReturnsFalseForEmptyName(): void
    {
        $result = $this->dispatcher->AddEventListener('', function () {});
        $this->assertFalse($result);
    }

    public function testAddEventListenerForMultipleEvents(): void
    {
        $result = $this->dispatcher->AddEventListener(['event.a', 'event.b'], function () {});
        $this->assertTrue($result);
    }

    public function testClearRemovesAllListeners(): void
    {
        $called = false;
        $this->dispatcher->AddEventListener('clear.test', function () use (&$called) {
            $called = true;
            return true;
        });
        $this->dispatcher->Clear();
        $sender = new \stdClass();
        $this->dispatcher->Dispatch(new Event($sender, 'clear.test'));
        $this->assertFalse($called);
    }
}
