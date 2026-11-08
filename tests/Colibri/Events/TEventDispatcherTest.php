<?php

use PHPUnit\Framework\TestCase;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\Event;
use Colibri\Events\EventDispatcher;

class TEventDispatcherTest extends TestCase
{
    private object $testObject;

    protected function setUp(): void
    {
        // Create a concrete class using the trait
        $this->testObject = new class {
            use TEventDispatcher;
        };
        EventDispatcher::Instance()->Clear();
    }

    protected function tearDown(): void
    {
        EventDispatcher::Instance()->Clear();
    }

    public function testHandleEventRegistersListener(): void
    {
        $called = false;
        $this->testObject->HandleEvent('test.trait.event', function (Event $event, mixed $args) use (&$called) {
            $called = true;
            return true;
        });
        $this->testObject->DispatchEvent('test.trait.event');
        $this->assertTrue($called);
    }

    public function testDispatchEventReturnsObjectOrNull(): void
    {
        $result = $this->testObject->DispatchEvent('nonexistent.event');
        $this->assertNull($result);
    }

    public function testHandleEventReturnsSelf(): void
    {
        $result = $this->testObject->HandleEvent('some.event', function () {});
        $this->assertSame($this->testObject, $result);
    }

    public function testRemoveHandlerReturnsSelf(): void
    {
        $listener = function () {};
        $this->testObject->HandleEvent('remove.test', $listener);
        $result = $this->testObject->RemoveHandler('remove.test', $listener);
        $this->assertSame($this->testObject, $result);
    }
}
