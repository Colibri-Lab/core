<?php

use PHPUnit\Framework\TestCase;
use Colibri\Events\Handlers\LocalClosure;
use Colibri\Events\Event;

class LocalClosureTest extends TestCase
{
    public function testConstructorCreatesInstance(): void
    {
        $closure = new LocalClosure(function () {});
        $this->assertInstanceOf(LocalClosure::class, $closure);
    }

    public function testInvokeCallsCallable(): void
    {
        $called = false;
        $closure = new LocalClosure(function (Event $event, mixed $args) use (&$called) {
            $called = true;
            return true;
        });

        $sender = new \stdClass();
        $event = new Event($sender, 'test.event');
        $closure->Invoke($event, null);
        $this->assertTrue($called);
    }

    public function testInvokePassesEventArgument(): void
    {
        $receivedEvent = null;
        $closure = new LocalClosure(function (Event $event, mixed $args) use (&$receivedEvent) {
            $receivedEvent = $event;
            return true;
        });

        $sender = new \stdClass();
        $event = new Event($sender, 'my.event');
        $closure->Invoke($event, null);
        $this->assertSame($event, $receivedEvent);
    }

    public function testInvokePassesArgs(): void
    {
        $receivedArgs = null;
        $closure = new LocalClosure(function (Event $event, mixed $args) use (&$receivedArgs) {
            $receivedArgs = $args;
            return true;
        });

        $sender = new \stdClass();
        $event = new Event($sender, 'test.event');
        $args = ['foo' => 'bar'];
        $closure->Invoke($event, $args);
        $this->assertEquals($args, $receivedArgs);
    }

    public function testInvokeWithObjectAndMethodString(): void
    {
        $obj = new class {
            public bool $wasCalled = false;
            public function handleEvent(Event $event, mixed $args): bool
            {
                $this->wasCalled = true;
                return true;
            }
        };

        $closure = new LocalClosure('handleEvent', $obj);
        $sender = new \stdClass();
        $event = new Event($sender, 'test.event');
        $closure->Invoke($event, null);
        $this->assertTrue($obj->wasCalled);
    }
}
