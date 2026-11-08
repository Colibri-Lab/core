<?php

use PHPUnit\Framework\TestCase;
use Colibri\Events\Event;

class EventTest extends TestCase
{
    public function testConstructorCreatesEvent(): void
    {
        $sender = new \stdClass();
        $event = new Event($sender, 'test.event');
        $this->assertInstanceOf(Event::class, $event);
    }

    public function testNameProperty(): void
    {
        $sender = new \stdClass();
        $event = new Event($sender, 'my.event');
        $this->assertEquals('my.event', $event->name);
    }

    public function testSenderProperty(): void
    {
        $sender = new \stdClass();
        $sender->id = 42;
        $event = new Event($sender, 'test.event');
        $this->assertSame($sender, $event->sender);
    }

    public function testUnknownPropertyReturnsNull(): void
    {
        $sender = new \stdClass();
        $event = new Event($sender, 'test.event');
        $this->assertNull($event->unknownProperty);
    }

    public function testConstructorWithStringName(): void
    {
        $sender = new \stdClass();
        $event = new Event($sender, 'namespace.action');
        $this->assertEquals('namespace.action', $event->name);
    }
}
