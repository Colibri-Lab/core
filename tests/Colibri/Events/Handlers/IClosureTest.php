<?php

use PHPUnit\Framework\TestCase;
use Colibri\Events\Handlers\IClosure;

class IClosureTest extends TestCase
{
    public function testIClosureCanBeMocked(): void
    {
        $mock = $this->createMock(IClosure::class);
        $this->assertInstanceOf(IClosure::class, $mock);
    }

    public function testInvokeMethod(): void
    {
        $event = new \Colibri\Events\Event(new \stdClass(), 'test.event');
        $mock = $this->createMock(IClosure::class);
        $mock->method('Invoke')->willReturn(true);
        $this->assertTrue($mock->Invoke($event, null));
    }

    public function testAsyncInvokeReturnsNullByDefault(): void
    {
        $event = new \Colibri\Events\Event(new \stdClass(), 'test.event');
        $mock = $this->createMock(IClosure::class);
        $mock->method('AsyncInvoke')->willReturn(null);
        $this->assertNull($mock->AsyncInvoke($event, null));
    }

    public function testSerializeMethod(): void
    {
        $mock = $this->createMock(IClosure::class);
        $mock->method('Serialize')->willReturn('serialized_data');
        $this->assertEquals('serialized_data', $mock->Serialize());
    }

    public function testUnserializeMethod(): void
    {
        $mock = $this->getMockForAbstractClass(IClosure::class);
        $this->assertInstanceOf(IClosure::class, $mock);
    }
}
