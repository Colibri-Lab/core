<?php

use PHPUnit\Framework\TestCase;
use Colibri\Queue\Exception;

class QueueExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new Exception('queue error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(Exception::class, $e);
    }

    public function testMessage(): void
    {
        $e = new Exception('queue is full');
        $this->assertEquals('queue is full', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new Exception('error', 503);
        $this->assertEquals(503, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(Exception::class);
        throw new Exception('test');
    }
}
